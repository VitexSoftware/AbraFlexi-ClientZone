<?php

declare(strict_types=1);

/**
 * clientzone - Product image cache / download from AbraFlexi attachments.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2026 VitexSoftware v.s.cz
 */

namespace ClientZone;

/**
 * Load product images from local cache or AbraFlexi přílohy (note = type).
 */
class ProductImager
{
    public string $baseDir = '/var/cache/abraflexi-clientzone/';

    /** @var int|string */
    public $id = null;

    /**
     * Absolute path to the packaged no-image placeholder.
     */
    public static function noImagePath(): string
    {
        return \dirname(__DIR__).'/images/noimage.png';
    }

    /**
     * @param int|string $id cenik id
     */
    public function __construct($id)
    {
        $this->id = is_numeric($id) ? (int) $id : $id;
    }

    /**
     * @return array<string, string> mime => body
     */
    public function getProductImage(string $type = 'image'): array
    {
        foreach (['jpg', 'jpeg', 'png', 'gif', 'svg'] as $ext) {
            if ($this->imageExists($type, $ext)) {
                $path = $this->imageDir().'/'.$type.'.'.$ext;
                $body = (string) file_get_contents($path);

                return [$this->mimeForExtension($ext) => $body];
            }
        }

        // Legacy broken cache from when ext was empty (thumbnail.)
        $legacy = $this->imageDir().'/'.$type.'.';
        if (is_readable($legacy) && filesize($legacy) > 0) {
            $body = (string) file_get_contents($legacy);
            [$mime, $ext] = $this->detectMimeAndExt($body, '');
            if ($ext !== '') {
                @rename($legacy, $this->imageDir().'/'.$type.'.'.$ext);

                return [$mime => $body];
            }
        }

        $image = $this->download($type);
        $body = current($image);
        if (is_string($body) && $body !== '') {
            [$mime, $ext] = $this->detectMimeAndExt($body, (string) key($image));
            $normalized = [$mime => $body];
            $this->storeToCache($normalized, $type, $ext);

            return $normalized;
        }

        $placeholder = self::noImagePath();
        $body = is_readable($placeholder) ? (string) file_get_contents($placeholder) : '';

        return ['image/png' => $body];
    }

    /**
     * Download attachment from AbraFlexi where poznam matches $type.
     *
     * @return array<string, string|null>
     */
    public function download(string $type): array
    {
        $result = null;
        $mime = '';

        try {
            $object = new \AbraFlexi\Cenik();
            $object->setMyKey($this->id);
            $fburl = $object->getAbraFlexiURL();

            $object->defaultUrlParams['detail'] = 'custom:id,poznam';
            $allAttachments = \AbraFlexi\Priloha::getAttachmentsList($object);
        } catch (\Throwable $e) {
            return [$mime => $result];
        }

        foreach ($allAttachments as $attachment) {
            if (($attachment['poznam'] ?? '') !== $type) {
                continue;
            }
            $response = $object->doCurlRequest(
                $fburl.'/prilohy/'.$attachment['id'].'/content',
                'GET',
            );
            if ($response == 200 && is_string($object->lastCurlResponse) && $object->lastCurlResponse !== '') {
                $result = $object->lastCurlResponse;
                // AbraFlexi\RO::getResponseFormat() returns a suffix like "png", not a MIME type.
                $mime = (string) $object->getResponseFormat();
                break;
            }
        }

        return [$mime => $result];
    }

    public function getProductThumbnail(): array
    {
        return $this->getProductImage('thumbnail');
    }

    public function imageExists(string $type, string $ext): bool
    {
        return is_readable($this->imageDir().'/'.$type.'.'.$ext);
    }

    public function imageDir(): string
    {
        return $this->baseDir.$this->id;
    }

    /**
     * @param array<string, string> $image
     */
    public static function output(array $image): void
    {
        $mime = (string) key($image);
        $body = (string) current($image);
        if ($mime === '' || !str_contains($mime, '/')) {
            [$mime] = (new self(0))->detectMimeAndExt($body, $mime);
        }
        if (!headers_sent()) {
            header('Content-Type: '.$mime);
            header('Content-Length: '.\strlen($body));
            header('Cache-Control: public, max-age=86400');
        }
        echo $body;
    }

    /**
     * @param array<string, string> $image
     */
    public function storeToCache(array $image, string $type, string $ext = ''): int|false
    {
        $body = (string) current($image);
        if ($ext === '') {
            [, $ext] = $this->detectMimeAndExt($body, (string) key($image));
        }
        if ($ext === '') {
            $ext = 'png';
        }

        $dir = $this->imageDir();
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return false;
        }

        $d = dir($dir);
        if ($d !== false) {
            while (false !== ($entry = $d->read())) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }
                if (str_starts_with($entry, $type.'.')) {
                    @unlink($dir.'/'.$entry);
                }
            }
            $d->close();
        }

        return file_put_contents($dir.'/'.$type.'.'.$ext, $body);
    }

    public static function getThumbnailUrl($id): string
    {
        return 'prdimg.php?type=thumbnail&id='.$id;
    }

    /**
     * Map AbraFlexi format suffix or Content-Type to MIME + file extension.
     *
     * @return array{0: string, 1: string} [mime, ext]
     */
    public function detectMimeAndExt(string $body, string $hint = ''): array
    {
        $hint = strtolower(trim(explode(';', $hint)[0]));
        $hint = str_replace('image/', '', $hint);

        $map = [
            'png' => ['image/png', 'png'],
            'jpg' => ['image/jpeg', 'jpg'],
            'jpeg' => ['image/jpeg', 'jpg'],
            'gif' => ['image/gif', 'gif'],
            'svg' => ['image/svg+xml', 'svg'],
            'svg+xml' => ['image/svg+xml', 'svg'],
            'webp' => ['image/webp', 'webp'],
        ];

        if ($hint !== '' && isset($map[$hint])) {
            return $map[$hint];
        }

        if (str_starts_with($body, "\x89PNG")) {
            return $map['png'];
        }
        if (str_starts_with($body, "\xFF\xD8\xFF")) {
            return $map['jpeg'];
        }
        if (str_starts_with($body, 'GIF8')) {
            return $map['gif'];
        }
        if (str_starts_with($body, 'RIFF') && str_contains(substr($body, 0, 16), 'WEBP')) {
            return $map['webp'];
        }
        if (str_contains(substr($body, 0, 256), '<svg') || str_starts_with(ltrim($body), '<?xml')) {
            return $map['svg'];
        }

        return ['image/png', 'png'];
    }

    public function mimeForExtension(string $ext): string
    {
        return $this->detectMimeAndExt('', $ext)[0];
    }
}
