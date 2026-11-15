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
 * Search in the catalog and in the documents of the signed in customer.
 *
 * The search term goes into an AbraFlexi filter, so it is reduced to harmless characters
 * first: a quote in the term would otherwise change the filter and could show documents of
 * other customers. Documents are always limited to the company of the customer.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class Searcher
{
    /**
     * Shortest term that is searched for.
     */
    public const MIN_LENGTH = 2;

    /**
     * Longest term that is searched for.
     */
    public const MAX_LENGTH = 50;

    /**
     * Rows of one group at most.
     */
    public const LIMIT = 50;

    /**
     * Labels that make a pricelist item part of the catalog.
     */
    public const CATALOG_LABELS = ['ESHOP', 'AKTIVNI'];

    /**
     * Columns searched in the pricelist.
     */
    public const PRICELIST_COLUMNS = ['kod', 'nazev', 'popis'];

    /**
     * Columns searched in the documents, per evidence.
     */
    public const DOCUMENT_COLUMNS = [
        'faktura-vydana' => ['kod', 'varSym', 'popis', 'poznam'],
        'objednavka-prijata' => ['kod', 'popis', 'poznam'],
    ];

    /**
     * Search term reduced to letters, digits, spaces and - _ . / @ (what codes, names and
     * variable symbols are made of).
     *
     * @return string empty when nothing usable is left
     */
    public static function sanitizeTerm(?string $term): string
    {
        $clean = (string) preg_replace('/[^\p{L}\p{N}\s\-_.\/@]+/u', ' ', (string) $term);
        $clean = trim((string) preg_replace('/\s+/u', ' ', $clean));

        return mb_substr($clean, 0, self::MAX_LENGTH);
    }

    /**
     * Is the term worth searching for?
     */
    public static function isSearchable(string $cleanTerm): bool
    {
        return mb_strlen($cleanTerm) >= self::MIN_LENGTH;
    }

    /**
     * AbraFlexi filter: the term in any of the columns.
     *
     * @param string             $cleanTerm sanitized term (see sanitizeTerm)
     * @param array<int, string> $columns   columns to look in
     */
    public static function likeCondition(string $cleanTerm, array $columns): string
    {
        return '('.implode(' or ', array_map(
            static fn (string $column): string => $column." like similar '".$cleanTerm."'",
            $columns,
        )).')';
    }

    /**
     * Filter for the catalog: the term in the item and the item sold by the shop.
     */
    public static function pricelistCondition(string $cleanTerm): string
    {
        $labels = array_map(static fn (string $label): string => "stitky = 'code:".$label."'", self::CATALOG_LABELS);

        return self::likeCondition($cleanTerm, self::PRICELIST_COLUMNS).' and '.implode(' and ', $labels);
    }

    /**
     * Filter for the documents: the term and the company of the customer.
     *
     * @param string $companyCondition condition of the company, e.g. "firma = 'code:POKUS'" (Customer::getFirmaCondition)
     */
    public static function documentCondition(string $cleanTerm, string $evidence, string $companyCondition): string
    {
        $columns = self::DOCUMENT_COLUMNS[$evidence] ?? ['kod'];

        return '('.$companyCondition.') and '.self::likeCondition($cleanTerm, $columns);
    }

    /**
     * Code without the "code:" prefix AbraFlexi puts on references.
     */
    public static function plainCode(string $code): string
    {
        return (string) preg_replace('/^code:/i', '', trim($code));
    }

    /**
     * Service name that orders a pricelist item: the order plugin of the item or the common one.
     *
     * @param array<string, string> $plugins plugin names by the product code they sell
     */
    public static function orderService(string $code, array $plugins): string
    {
        return $plugins[$code] ?? 'Common&kod='.$code;
    }

    /**
     * Search everything the visitor may see.
     *
     * @param string $term typed by the visitor, sanitized here
     *
     * @return array<string, array<int, array{code: string, name: string, info: string, url: string}>> rows by group
     *                                                                                                  ("pricelist", "faktura-vydana", "objednavka-prijata"); empty groups are left out
     */
    public function search(string $term): array
    {
        $clean = self::sanitizeTerm($term);

        if (!self::isSearchable($clean)) {
            return [];
        }

        $results = [];
        $items = $this->pricelist($clean);

        if ($items !== []) {
            $results['pricelist'] = $items;
        }

        $user = \Ease\Shared::user();

        if ($user instanceof Customer && $user->isLogged()) {
            foreach (array_keys(self::DOCUMENT_COLUMNS) as $evidence) {
                $documents = $this->documents($clean, $evidence, $user->getFirmaCondition());

                if ($documents !== []) {
                    $results[$evidence] = $documents;
                }
            }
        }

        return $results;
    }

    /**
     * @return array<int, array{code: string, name: string, info: string, url: string}>
     */
    private function pricelist(string $cleanTerm): array
    {
        $plugins = [];

        foreach ((new OrderItem())->plugins as $pluginName => $plugin) {
            $plugins[$plugin->productCode] = $pluginName;
        }

        $pricer = new \AbraFlexi\Cenik();
        $pricer->throwException = false;
        $rows = $pricer->getColumnsFromAbraFlexi(
            ['id', 'kod', 'nazev', 'cenaZaklBezDph'],
            [self::pricelistCondition($cleanTerm), 'limit' => self::LIMIT],
        );
        $found = [];

        foreach (\is_array($rows) ? $rows : [] as $row) {
            $code = self::plainCode((string) ($row['kod'] ?? ''));
            $found[] = [
                'code' => $code,
                'name' => (string) ($row['nazev'] ?? ''),
                'info' => number_format((float) ($row['cenaZaklBezDph'] ?? 0), 2, ',', ' ').' '._('excl. VAT'),
                'url' => 'orderform.php?service='.self::orderService($code, $plugins),
            ];
        }

        return $found;
    }

    /**
     * @return array<int, array{code: string, name: string, info: string, url: string}>
     */
    private function documents(string $cleanTerm, string $evidence, string $companyCondition): array
    {
        $documents = new \AbraFlexi\RO(null, ['evidence' => $evidence]);
        $documents->throwException = false;
        $rows = $documents->getColumnsFromAbraFlexi(
            ['id', 'kod', 'popis', 'sumCelkem'],
            [self::documentCondition($cleanTerm, $evidence, $companyCondition), 'limit' => self::LIMIT],
        );
        $found = [];

        foreach (\is_array($rows) ? $rows : [] as $row) {
            $found[] = [
                'code' => self::plainCode((string) ($row['kod'] ?? '')),
                'name' => (string) ($row['popis'] ?? ''),
                'info' => isset($row['sumCelkem']) ? number_format((float) $row['sumCelkem'], 2, ',', ' ') : '',
                'url' => 'document.php?id='.(int) $row['id'].'&evidence='.$evidence,
            ];
        }

        return $found;
    }
}
