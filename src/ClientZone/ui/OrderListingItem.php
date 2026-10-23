<?php

declare(strict_types=1);

namespace ClientZone\ui;

/**
 * One row in My Orders listing.
 *
 * @author vitex
 */
class OrderListingItem extends \Ease\TWB5\Row
{
    /**
     * @param array<string, mixed> $itemdata
     */
    public function __construct(array $itemdata, string $evidence)
    {
        parent::__construct();

        $id = $itemdata['id'] ?? '';
        $this->addColumn(2, new \Ease\Html\StrongTag(new \Ease\Html\ATag(
            'document.php?id='.$id.'&evidence='.$evidence,
            (string) ($itemdata['kod'] ?? $id),
            ['title' => _('Show')],
        )));

        $mena = isset($itemdata['mena'])
            ? str_replace('code:', '', self::relationValue($itemdata['mena']))
            : 'Kč';
        $this->addColumn(2, ($itemdata['sumCelkem'] ?? '').' '.$mena);
        $this->addColumn(1, self::formatDate($itemdata['datSplat'] ?? null));

        switch ($evidence) {
            case 'faktura-vydana':
                if (!empty($itemdata['stavUhrK']) && str_contains((string) $itemdata['stavUhrK'], 'stavUhr.uhrazeno')) {
                    $status = new \Ease\TWB5\Badge(_('Settled'), 'success');
                } else {
                    $status = self::dueLabel($itemdata['datSplat'] ?? null);
                }
                break;
            case 'objednavka-prijata':
                if (($itemdata['stavUzivK'] ?? '') === 'stavDoklObch.hotovo') {
                    $status = new \Ease\TWB5\Badge(_('Done'), 'success');
                } else {
                    $status = new \Ease\TWB5\Badge(
                        _('Open').': '.str_replace('stavDoklObch.', '', (string) ($itemdata['stavUzivK'] ?? '')),
                        'warning',
                    );
                }
                break;
            default:
                $status = '';
                break;
        }
        $this->addColumn(2, $status);

        $links = [
            self::iconLink(
                'document.php?id='.$id.'&evidence='.$evidence,
                'images/oko.svg',
                _('Show'),
            ),
            self::iconLink(
                'getpdf.php?id='.$id.'&evidence='.$evidence,
                'images/download.svg',
                _('Download'),
            ),
            self::iconLink(
                'maildocument.php?id='.$id.'&evidence='.$evidence,
                'images/email.svg',
                _('Send'),
            ),
        ];

        if ($evidence === 'faktura-vydana') {
            $links[] = self::iconLink(
                'getisdoc.php?id='.$id.'&evidence='.$evidence,
                'images/ISDOC.png',
                _('ISDOC'),
            );
        }

        $this->addColumn(2, $links);
    }

    /**
     * Icon action link for order row.
     *
     * @param array<string, string> $imgProperties
     */
    public static function iconLink(
        string $href,
        string $img,
        string $title,
        array $imgProperties = ['height' => '20px'],
    ): \Ease\Html\ATag {
        return new \Ease\Html\ATag(
            $href,
            new \Ease\Html\ImgTag($img, $title, $imgProperties),
            ['title' => $title, 'class' => 'cz-order-action'],
        );
    }

    /**
     * Scalar value from AbraFlexi Relation or plain field.
     */
    public static function relationValue(mixed $value): string
    {
        if ($value instanceof \AbraFlexi\Relation) {
            return (string) $value;
        }

        return $value === null ? '' : (string) $value;
    }

    /**
     * Human label from Relation::showAs or legacy @showAs key.
     */
    public static function relationShowAs(mixed $value, mixed $legacyShowAs = null): string
    {
        if ($value instanceof \AbraFlexi\Relation && !empty($value->showAs)) {
            return (string) $value->showAs;
        }
        if ($legacyShowAs !== null && $legacyShowAs !== '') {
            return (string) $legacyShowAs;
        }

        return self::relationValue($value);
    }

    /**
     * Format AbraFlexi date / DateTime / string for listing.
     */
    public static function formatDate(mixed $date): string
    {
        if ($date instanceof \AbraFlexi\Date) {
            return $date->isNull ? '' : $date->format('d.m.Y');
        }
        if ($date instanceof \DateTimeInterface) {
            return $date->format('d.m.Y');
        }
        if (\is_string($date) && $date !== '') {
            return (new \AbraFlexi\Date($date))->format('d.m.Y');
        }

        return '';
    }

    /**
     * @param \DateTimeInterface|string|null $date
     */
    public static function dueLabel($date): \Ease\TWB5\Badge
    {
        if ($date === null || $date === ''
            || ($date instanceof \AbraFlexi\Date && $date->isNull)
        ) {
            return new \Ease\TWB5\Badge(_('No due date'), 'secondary');
        }

        $days = \AbraFlexi\FakturaVydana::overdueDays($date);
        if ($days < 0) {
            return new \Ease\TWB5\Badge(
                sprintf(_('%s days to due'), (string) abs($days)),
                'success',
            );
        }

        return new \Ease\TWB5\Badge(
            sprintf(_('%s days after due'), (string) abs($days)),
            $days > 14 ? 'danger' : 'warning',
        );
    }
}
