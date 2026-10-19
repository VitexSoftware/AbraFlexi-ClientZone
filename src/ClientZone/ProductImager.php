<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ClientZone;

/**
 * Description of ProductImage
 *
 * @author vitex
 */
class ProductImager
{
    /**
     *
     * @var type
     */
    public $baseDir = '/var/cache/abraflexi-clientzone/';

    /**
     *
     * @var type
     */
    public $id = null;

    /**
     *
     * @param type $id
     */
    public function __construct($id)
    {
        $this->id = is_numeric($id) ? intval($id) : $id;
    }

    /**
     *
     * @param type $type
     * @return type
     */
    function getProductImage($type = 'image')
    {
        $mime = 'image/png';
        if ($this->imageExists($type, 'jpg')) {
            $body = file_get_contents($this->baseDir.$this->id.'/'.$type.'.jpg');
            $mime = 'image/jpeg';
        } elseif ($this->imageExists($type, 'png')) {
            $body = file_get_contents($this->baseDir.$this->id.'/'.$type.'.png');
        } elseif ($this->imageExists($type, 'svg')) {
            $mime = 'image/svg+xml';
            $body = file_get_contents($this->baseDir.$this->id.'/'.$type.'.svg');
        } else {
            $image = $this->download($type);
            if (strlen(current($image))) {
                $this->storeToCache($image, $type);
                $mime = key($image);
                $body = current($image);
            } else {
                $body = file_get_contents('images/noimage.png');
            }
        }
        return [$mime => $body];
    }

    /**
     * Stahne obrázek z flexibee
     *
     * @param type $type
     * @return type
     */
    public function download($type)
    {
        $result      = null;
        $mime        = 'nothing/none';
        $object      = new \AbraFlexi\Cenik();
        $object->setMyKey($this->id);
        $fburl       = $object->getAbraFlexiURL();
        $attachments = [];

        $object->defaultUrlParams['detail'] = 'custom:id,poznam';
        $allAttachments                     = \AbraFlexi\Priloha::getAttachmentsList($object);

        foreach ($allAttachments as $atId => $attachment) {
            if ($attachment['poznam'] == $type) {
                $response = $object->doCurlRequest($fburl.'/prilohy/'.$attachment['id'].'/content',
                    'GET');
                if ($response == 200) {
                    $result = $object->lastCurlResponse;
                    $mime   = $object->getResponseFormat();
                    break;
                }
            }
        }
        return [$mime => $result];
    }

    function getProductThumbnail()
    {
        return $this->getProductImage('thumbnail');
    }

    public function imageExists($type, $ext)
    {
        return file_exists($this->imageDir().'/'.$type.'.'.$ext);
    }

    public function imageDir()
    {
        return $this->baseDir.$this->id;
    }

    public static function output(array $image)
    {
        $mime = key($image);
        $body = current($image);
        header('Content-Type: '.$mime);
        echo $body;
    }

    /**
     * 
     * @param array $image
     * @param type $type
     * @return type
     */
    public function storeToCache(array $image, $type)
    {
        $mime = key($image);
        switch ($mime) {
            case 'image/png':
                $ext = 'png';
                break;
            case 'image/jpeg':
                $ext = 'jpg';
                break;
            case 'image/gif':
                $ext = 'gif';
                break;
            case 'image/svg+xml':
                $ext = 'svg';
                break;
        }
        $dir = $this->imageDir();
        if (!file_exists($dir)) {
            mkdir($dir);
        }
        $d     = dir($dir);
        while (false !== ($entry = $d->read())) {
            if (strstr($entry, $type)) {
                unlink($dir.'/'.$entry);
            }
        }
        $d->close();
        return file_put_contents($dir.'/'.$type.'.'.$ext, current($image));
    }

    public static function getThumbnailUrl($id)
    {
        return 'prdimg.php?type=thumbnail&id='.$id;
    }

}
