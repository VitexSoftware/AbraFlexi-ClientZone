<?php

declare(strict_types=1);

/**
 * DataTables engine for customer's contracts (smlouva).
 *
 * Only customer-visible fields are fetched — internal notes (poznam) and
 * responsible persons, cost centres etc. are deliberately left out.
 *
 * @author     Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2026 VitexSoftware
 */

namespace ClientZone;

/**
 * Lister for smlouva — used by DocumentDataTable / abraflexidata.php.
 */
class ContractLister extends \AbraFlexi\Smlouva
{
    use DocumentLister;

    /**
     * Fields a customer may see on a contract (list and detail).
     */
    public const VISIBLE_FIELDS = [
        'kod', 'nazev', 'popis', 'typSml', 'stavSml', 'smlouvaOd', 'smlouvaDo',
        'datumPodepsani', 'frekFakt', 'lastUpdate',
    ];

    public function columns($columns = [])
    {
        return [
            ['name' => 'kod', 'type' => 'text', 'label' => _('Contract number')],
            ['name' => 'nazev', 'type' => 'text', 'label' => _('Name')],
            ['name' => 'smlouvaOd', 'type' => 'text', 'label' => _('Valid from')],
            ['name' => 'smlouvaDo', 'type' => 'text', 'label' => _('Valid to')],
            ['name' => 'stavSml', 'type' => 'text', 'label' => _('Status')],
            ['name' => 'lastUpdate', 'type' => 'text', 'label' => _('Last change')],
        ];
    }

    public function completeDataRow(array $dataRowRaw): array
    {
        $base = $this->normalizeScalars($dataRowRaw);
        $id = (string) ($dataRowRaw['id'] ?? '');
        $kod = (string) ($dataRowRaw['kod'] ?? $id);

        return [
            'kod' => (string) new \Ease\Html\ATag(
                'contract.php?id='.$id,
                '<strong>'.\htmlspecialchars($kod, \ENT_QUOTES).'</strong>',
                ['title' => _('Show')],
            ),
            'nazev' => \htmlspecialchars((string) ($base['nazev'] ?? ''), \ENT_QUOTES),
            'smlouvaOd' => self::displayValue($dataRowRaw['smlouvaOd'] ?? null),
            'smlouvaDo' => self::displayValue($dataRowRaw['smlouvaDo'] ?? null),
            'stavSml' => \htmlspecialchars(self::displayValue($dataRowRaw['stavSml'] ?? null), \ENT_QUOTES),
            'lastUpdate' => self::displayValue($dataRowRaw['lastUpdate'] ?? null, true),
        ];
    }

    /**
     * Human readable form of any AbraFlexi value (relation, date, datetime, string).
     *
     * @param mixed $value
     * @param bool  $withTime include time of day (for datetime fields)
     */
    public static function displayValue(mixed $value, bool $withTime = false): string
    {
        if ($value === null || $value === false || $value === '') {
            return '';
        }

        if ($value instanceof \AbraFlexi\Relation) {
            return (string) ($value->showAs ?: str_replace('code:', '', (string) $value));
        }

        if ($value instanceof \AbraFlexi\Date) {
            return $value->isNull ? '' : $value->format('d.m.Y');
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format($withTime ? 'd.m.Y H:i' : 'd.m.Y');
        }

        $text = (string) $value;

        if (str_starts_with($text, 'code:')) {
            return substr($text, 5);
        }

        if ($withTime && preg_match('/^\d{4}-\d{2}-\d{2}/', $text) === 1) {
            try {
                return (new \DateTimeImmutable($text))->format('d.m.Y H:i');
            } catch (\Exception $e) {
                return $text;
            }
        }

        return $text;
    }
}
