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
 * Details of the supplier: the company the accounting unit in AbraFlexi is kept for.
 *
 * AbraFlexi keeps them in the evidence "nastaveni", but a user with limited rights
 * (typical for an integration account) may not read it. Whatever AbraFlexi does not
 * provide is therefore taken from the configuration (SUPPLIER_* options, see README).
 * A value delivered by AbraFlexi always wins.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class Supplier
{
    /**
     * Supplier detail => [configuration option, AbraFlexi "nastaveni" columns in order of preference].
     */
    public const FIELDS = [
        'name' => ['SUPPLIER_NAME', ['nazFirmy']],
        'ic' => ['SUPPLIER_IC', ['ic']],
        'dic' => ['SUPPLIER_DIC', ['dic']],
        'email' => ['SUPPLIER_EMAIL', ['email']],
        'phone' => ['SUPPLIER_PHONE', ['tel', 'mobil']],
        'web' => ['SUPPLIER_WEB', ['www']],
        'street' => ['SUPPLIER_STREET', ['ulice', 'uliceNazev']],
        'city' => ['SUPPLIER_CITY', ['mesto']],
        'zip' => ['SUPPLIER_ZIP', ['psc']],
    ];

    /**
     * @var array<string, string>
     */
    private array $details = [];

    /**
     * @var array<string, string> "abraflexi" or "config" for every detail that has a value
     */
    private array $sources = [];

    /**
     * @param array<int, array<string, mixed>> $abraFlexiRecords records of "nastaveni" (empty when AbraFlexi did not provide them)
     * @param array<string, mixed>             $configured       configuration options keyed by their names (SUPPLIER_*)
     */
    public function __construct(array $abraFlexiRecords = [], array $configured = [])
    {
        foreach (self::FIELDS as $detail => [$option, $columns]) {
            $value = self::fromRecords($abraFlexiRecords, $columns);
            $source = 'abraflexi';

            if ($value === '') {
                $value = self::clean($configured[$option] ?? null);
                $source = 'config';
            }

            $this->details[$detail] = $value;

            if ($value !== '') {
                $this->sources[$detail] = $source;
            }
        }
    }

    /**
     * Supplier as seen by the application now: AbraFlexi first, the configuration for the rest.
     *
     * AbraFlexi failing or refusing access to "nastaveni" is not an error, it only means the
     * configuration is used.
     */
    public static function load(): self
    {
        $configured = [];

        foreach (self::FIELDS as [$option]) {
            $configured[$option] = \Ease\Shared::cfg($option);
        }

        return new self(self::readAbraFlexi(), $configured);
    }

    /**
     * Value of one detail, empty string when nobody knows it.
     */
    public function get(string $detail): string
    {
        return $this->details[$detail] ?? '';
    }

    /**
     * @return array<string, string> all details, empty string for the unknown ones
     */
    public function all(): array
    {
        return $this->details;
    }

    /**
     * Where the detail came from: "abraflexi", "config", or null when it is unknown.
     */
    public function source(string $detail): ?string
    {
        return $this->sources[$detail] ?? null;
    }

    /**
     * Is there at least one way to reach the supplier by e-mail?
     */
    public function hasEmail(): bool
    {
        return $this->get('email') !== '';
    }

    /**
     * One line to tell the customer how to reach the supplier, empty when nothing is known.
     */
    public function contactLine(): string
    {
        return implode(', ', array_filter([$this->get('email'), $this->get('phone'), $this->get('web')]));
    }

    /**
     * First non-empty value among the columns, looking through all the records.
     *
     * @param array<int, array<string, mixed>> $records
     * @param array<int, string>               $columns
     */
    private static function fromRecords(array $records, array $columns): string
    {
        foreach ($columns as $column) {
            foreach ($records as $record) {
                $value = self::clean($record[$column] ?? null);

                if ($value !== '') {
                    return $value;
                }
            }
        }

        return '';
    }

    /**
     * Text value, empty for anything that carries no information.
     */
    private static function clean(mixed $value): string
    {
        if (\is_array($value) || (\is_object($value) && !$value instanceof \Stringable)) {
            return '';
        }

        return trim((string) $value);
    }

    /**
     * Read "nastaveni" from AbraFlexi.
     *
     * @return array<int, array<string, mixed>> empty when the evidence cannot be read
     */
    private static function readAbraFlexi(): array
    {
        $columns = ['id'];

        foreach (self::FIELDS as [, $abraFlexiColumns]) {
            $columns = array_merge($columns, $abraFlexiColumns);
        }

        try {
            $settings = new \AbraFlexi\RO(null, ['evidence' => 'nastaveni', 'ignore404' => true]);
            $rows = $settings->getColumnsFromAbraFlexi(array_values(array_unique($columns)));

            // A refusal ("You don't have permission to this action") is answered with an error status
            if ($settings->lastResponseCode !== 200 || !\is_array($rows)) {
                return [];
            }

            return array_values(array_filter($rows, 'is_array'));
        } catch (\Throwable) {
            return [];
        }
    }
}
