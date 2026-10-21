<?php

declare(strict_types=1);

/**
 * DataTables engine for customer's received orders.
 *
 * @author     Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2026 VitexSoftware
 */

namespace ClientZone;

use ClientZone\ui\OrderListingItem;

/**
 * Lister for objednávka-prijata — used by DocumentDataTable / abraflexidata.php.
 */
class OrderLister extends \AbraFlexi\ObjednavkaPrijata
{
    use DocumentLister;

    public function columns($columns = [])
    {
        return [
            ['name' => 'kod', 'type' => 'text', 'label' => _('Code')],
            ['name' => 'sumCelkem', 'type' => 'text', 'label' => _('Amount')],
            ['name' => 'mena', 'type' => 'text', 'label' => _('Currency')],
            ['name' => 'datVyst', 'type' => 'text', 'label' => _('Issued')],
            ['name' => 'stavUzivK', 'type' => 'text', 'label' => _('Status')],
            ['name' => 'actions', 'type' => 'text', 'label' => _('Actions'), 'searchable' => false],
        ];
    }

    protected function extraFetchColumns(): array
    {
        return ['id', 'stavUzivK', 'datVyst'];
    }

    public function completeDataRow(array $dataRowRaw): array
    {
        $base = $this->normalizeScalars($dataRowRaw);
        $id = (string) ($dataRowRaw['id'] ?? '');
        $evidence = $this->getEvidence();

        $kod = (string) ($dataRowRaw['kod'] ?? $id);
        $row = [
            'kod' => (string) new \Ease\Html\ATag(
                'document.php?id='.$id.'&evidence='.$evidence,
                '<strong>'.\htmlspecialchars($kod, \ENT_QUOTES).'</strong>',
                ['title' => _('Show')],
            ),
            'sumCelkem' => \htmlspecialchars((string) ($dataRowRaw['sumCelkem'] ?? ''), \ENT_QUOTES),
            'mena' => \htmlspecialchars(
                isset($dataRowRaw['mena'])
                    ? str_replace('code:', '', OrderListingItem::relationValue($dataRowRaw['mena']))
                    : 'Kč',
                \ENT_QUOTES,
            ),
            'datVyst' => (string) ($base['datVyst'] ?? OrderListingItem::formatDate($dataRowRaw['datVyst'] ?? null)),
            'stavUzivK' => '',
            'actions' => self::actionIcons($id, $evidence, false),
        ];

        if (($dataRowRaw['stavUzivK'] ?? '') === 'stavDoklObch.hotovo') {
            $row['stavUzivK'] = (string) new \Ease\TWB5\Badge(_('Done'), 'success');
        } else {
            $label = _('Open').': '.str_replace(
                'stavDoklObch.',
                '',
                (string) ($dataRowRaw['stavUzivK'] ?? ''),
            );
            $row['stavUzivK'] = (string) new \Ease\TWB5\Badge($label, 'warning');
        }

        return $row;
    }

    public static function actionIcons(string $id, string $evidence, bool $withIsdoc = false): string
    {
        $html = '';
        $items = [
            OrderListingItem::iconLink(
                'document.php?id='.$id.'&evidence='.$evidence,
                'images/oko.svg',
                _('Show'),
            ),
            OrderListingItem::iconLink(
                'getpdf.php?id='.$id.'&evidence='.$evidence.'&embed=true',
                'images/download.svg',
                _('Download'),
            ),
            OrderListingItem::iconLink(
                'maildocument.php?id='.$id.'&evidence='.$evidence,
                'images/email.svg',
                _('Send'),
            ),
        ];
        if ($withIsdoc) {
            $items[] = OrderListingItem::iconLink(
                'getisdoc.php?id='.$id.'&evidence='.$evidence,
                'images/ISDOC.png',
                _('ISDOC'),
            );
        }
        foreach ($items as $item) {
            $html .= $item->getRendered().' ';
        }

        return trim($html);
    }
}
