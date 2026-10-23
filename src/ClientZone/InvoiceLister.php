<?php

declare(strict_types=1);

/**
 * DataTables engine for customer's issued invoices.
 *
 * @author     Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2026 VitexSoftware
 */

namespace ClientZone;

use ClientZone\ui\OrderListingItem;

/**
 * Lister for faktura-vydana — used by DocumentDataTable / abraflexidata.php.
 */
class InvoiceLister extends \AbraFlexi\FakturaVydana
{
    use DocumentLister;

    public function columns($columns = [])
    {
        return [
            ['name' => 'kod', 'type' => 'text', 'label' => _('Code')],
            ['name' => 'sumCelkem', 'type' => 'text', 'label' => _('Amount')],
            ['name' => 'mena', 'type' => 'text', 'label' => _('Currency')],
            ['name' => 'datSplat', 'type' => 'text', 'label' => _('Due date')],
            ['name' => 'stavUhrK', 'type' => 'text', 'label' => _('Status')],
            ['name' => 'actions', 'type' => 'text', 'label' => _('Actions'), 'searchable' => false],
        ];
    }

    protected function extraFetchColumns(): array
    {
        return ['id', 'stavUhrK', 'datSplat', 'datVyst', 'storno'];
    }

    public function completeDataRow(array $dataRowRaw): array
    {
        $base = $this->normalizeScalars($dataRowRaw);
        $id = (string) ($dataRowRaw['id'] ?? '');
        $evidence = $this->getEvidence();
        $cancelled = self::isCancelled($dataRowRaw['storno'] ?? null);

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
            'datSplat' => (string) ($base['datSplat'] ?? OrderListingItem::formatDate($dataRowRaw['datSplat'] ?? null)),
            'stavUhrK' => '',
            'actions' => OrderLister::actionIcons($id, $evidence, true),
        ];

        $stavUhr = (string) ($dataRowRaw['stavUhrK'] ?? '');
        if ($cancelled) {
            $row['stavUhrK'] = (string) new \Ease\TWB5\Badge(_('Cancelled'), 'secondary');
            // Cancelled documents stay listed, but greyed out and without a due-date label.
            foreach (['kod', 'sumCelkem', 'mena', 'datSplat'] as $cell) {
                $row[$cell] = '<span class="text-muted">'.$row[$cell].'</span>';
            }
        } elseif ($stavUhr !== '' && str_contains($stavUhr, 'stavUhr.uhrazeno')) {
            $row['stavUhrK'] = (string) new \Ease\TWB5\Badge(_('Settled'), 'success');
        } else {
            $row['stavUhrK'] = (string) OrderListingItem::dueLabel($dataRowRaw['datSplat'] ?? null);
        }

        return $row;
    }

    /**
     * AbraFlexi returns the storno flag as boolean or as its string form.
     *
     * @param mixed $storno
     */
    public static function isCancelled(mixed $storno): bool
    {
        return \in_array($storno, [true, 1, 'true', '1'], true);
    }
}
