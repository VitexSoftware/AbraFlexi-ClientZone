<?php

declare(strict_types=1);

/**
 * This file is part of the ClientZone package
 *
 * https://github.com/VitexSoftware/AbraFlexi-ClientZone
 *
 * (c) Vítězslav Dvořák <http://vitexsoftware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ClientZone;

/**
 * Everything the customer needs to know about a product of the catalog before buying it.
 *
 * Only items the shop sells (labels ESHOP and AKTIVNI, see Searcher::CATALOG_LABELS) can be shown:
 * the page must not reveal the rest of the pricelist.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class ProductDetail
{
    /**
     * Longest product code accepted.
     */
    public const MAX_CODE_LENGTH = 50;

    /**
     * Product code from the address reduced to what codes are made of (it goes into an AbraFlexi filter).
     *
     * @return string empty when nothing usable is left
     */
    public static function sanitizeCode(?string $code): string
    {
        $clean = (string) preg_replace('/[^\p{L}\p{N} \-_.\/]+/u', '', (string) $code);

        return mb_substr(trim($clean), 0, self::MAX_CODE_LENGTH);
    }

    /**
     * AbraFlexi filter that finds the item and nothing else than an item sold by the shop.
     *
     * @param string $cleanCode sanitized code (see sanitizeCode)
     */
    public static function condition(string $cleanCode): string
    {
        $labels = array_map(static fn (string $label): string => "stitky = 'code:".$label."'", Searcher::CATALOG_LABELS);

        return "kod eq '".$cleanCode."' and ".implode(' and ', $labels);
    }

    /**
     * Price with VAT.
     *
     * The rate of the item wins: AbraFlexi may deliver the price "with VAT" equal to the price
     * without it for an item entered without VAT, and the customer would pay more than shown.
     *
     * @param float      $withoutVat price without VAT
     * @param float      $rate       VAT rate in percent (szbDph), 0 when the item has none
     * @param null|float $delivered  price with VAT as AbraFlexi delivers it
     */
    public static function priceWithVat(float $withoutVat, float $rate, ?float $delivered = null): float
    {
        if ($rate > 0) {
            return round($withoutVat * (1 + $rate / 100), 2);
        }

        return $delivered ?? $withoutVat;
    }

    /**
     * Percent of VAT from the price without and with it.
     */
    public static function vatPercent(float $withoutVat, float $withVat): int
    {
        if ($withoutVat <= 0.0 || $withVat <= $withoutVat) {
            return 0;
        }

        return (int) round(($withVat / $withoutVat - 1) * 100);
    }

    /**
     * Stock type of AbraFlexi as the customer reads it.
     */
    public static function stockTypeLabel(string $stockType): string
    {
        return match ($stockType) {
            'typZasoby.sluzba' => _('Service'),
            'typZasoby.zbozi' => _('Goods'),
            'typZasoby.material' => _('Material'),
            'typZasoby.poplatek' => _('Fee'),
            default => '',
        };
    }

    /**
     * Price as text: "1 234,50 Kč".
     */
    public static function formatPrice(float $price): string
    {
        return number_format($price, 2, ',', ' ').' Kč';
    }

    /**
     * Order plugins by the product code they sell: the name that orders the product (the key
     * the order form takes as "service") and the plugin itself.
     *
     * @return array<string, array{service: string, plugin: OrderPlugin}>
     */
    public static function plugins(): array
    {
        $plugins = [];

        foreach ((new OrderItem())->plugins as $pluginName => $plugin) {
            $plugins[(string) $plugin->productCode] = ['service' => (string) $pluginName, 'plugin' => $plugin];
        }

        return $plugins;
    }

    /**
     * The product with everything the page shows.
     *
     * @return null|array{id: int, code: string, name: string, description: string, stockType: string, stockTypeLabel: string, unit: string, unitName: string, priceWithoutVat: float, priceWithVat: float, vat: int, kind: string, labels: array<int, string>, service: string}
     */
    public static function find(string $code): ?array
    {
        $clean = self::sanitizeCode($code);

        if ($clean === '') {
            return null;
        }

        $pricer = new \AbraFlexi\Cenik();
        $pricer->throwException = false;
        // The full record: AbraFlexi counts the prices and the VAT rate only there, not in a list of columns
        $rows = $pricer->getColumnsFromAbraFlexi('full', [self::condition($clean), 'limit' => 1]);
        $row = \is_array($rows) ? (array) (current($rows) ?: []) : [];

        if ($row === [] || empty($row['id'])) {
            return null;
        }

        $kod = Searcher::plainCode((string) ($row['kod'] ?? $clean));
        $unit = BillingKind::plainCode((string) ($row['mj1'] ?? ''));
        $plugins = self::plugins();
        $plugin = $plugins[$kod]['plugin'] ?? null;
        $stockType = (string) ($row['typZasobyK'] ?? '');
        $withoutVat = (float) ($row['cenaZaklBezDph'] ?? 0);
        $rate = (float) ($row['szbDph'] ?? 0);
        $withVat = self::priceWithVat($withoutVat, $rate, isset($row['cenaZaklVcDph']) ? (float) $row['cenaZaklVcDph'] : null);

        return [
            'id' => (int) $row['id'],
            'code' => $kod,
            'name' => (string) ($row['nazev'] ?? $kod),
            'description' => trim((string) ($row['popis'] ?? '')),
            'stockType' => $stockType,
            'stockTypeLabel' => self::stockTypeLabel($stockType),
            'unit' => $unit,
            'unitName' => $unit === '' ? '' : self::unitName($unit),
            'priceWithoutVat' => $withoutVat,
            'priceWithVat' => $withVat,
            'vat' => $rate > 0 ? (int) round($rate) : self::vatPercent($withoutVat, $withVat),
            'kind' => BillingKind::determine($stockType, $unit, $row['stitky'] ?? null, $plugin?->billing),
            'labels' => array_values(array_filter(array_map(
                static fn (string $label): string => Searcher::plainCode($label),
                explode(',', (string) ($row['stitky'] ?? '')),
            ))),
            'service' => Searcher::orderService($kod, array_map(static fn (array $p): string => $p['service'], $plugins)),
        ];
    }

    /**
     * Name of a unit as the customer reads it ("Měsíc" gives "měsíc"), the code when AbraFlexi does not tell.
     */
    private static function unitName(string $unitCode): string
    {
        $units = new \AbraFlexi\RO(null, ['evidence' => 'merna-jednotka']);
        $units->throwException = false;
        $rows = $units->getColumnsFromAbraFlexi(['kod', 'nazev'], ['kod' => $unitCode]);
        $row = \is_array($rows) ? (array) (current($rows) ?: []) : [];
        $name = trim((string) ($row['nazev'] ?? ''));

        return mb_strtolower($name !== '' ? $name : $unitCode);
    }
}
