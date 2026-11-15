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
 * How a product is paid for: once, or invoiced again and again.
 *
 * The customer decides on this before buying, so it must not be guessed from the AbraFlexi
 * stock type alone: "service" covers both a monthly mailbox and an hour of training.
 * The kind is taken from (the first that says something wins):
 *
 *  1. a label of the pricelist item: RECURRING or ONETIME (the clearest, set by the salesman)
 *  2. the order plugin of the product (it knows what it provisions)
 *  3. the unit of a service: a period (month, year) means it is invoiced for every such period
 *  4. the stock type: goods, material and fees are bought once
 *
 * Whatever is none of these (a service in hours or in gigabytes) is only "service": the
 * page says what it is invoiced by and does not promise more than that.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
final class BillingKind
{
    public const ONE_TIME = 'one-time';
    public const RECURRING = 'recurring';
    public const SERVICE = 'service';

    public const LABEL_RECURRING = 'RECURRING';
    public const LABEL_ONE_TIME = 'ONETIME';

    /**
     * Units (codes in AbraFlexi, upper case) that are a period of time.
     */
    public const PERIOD_UNITS = ['MESIC', 'MES', 'ROK', 'ROKY', 'TYDEN', 'KVARTAL', 'CTVRTLETI', 'POLOLETI'];

    /**
     * Stock types bought once.
     */
    public const ONE_TIME_TYPES = ['typZasoby.zbozi', 'typZasoby.material', 'typZasoby.poplatek'];

    private function __construct()
    {
    }

    /**
     * @param string                         $stockType     typZasobyK of the item, e.g. "typZasoby.sluzba"
     * @param string                         $unit          unit code of the item (mj1), e.g. "MESIC" or "code:MESIC"
     * @param array<int, string>|string|null $labels        labels of the item
     * @param null|string                    $pluginBilling what the order plugin of the product says (RECURRING / ONE_TIME), null when there is none
     *
     * @return string ONE_TIME, RECURRING or SERVICE
     */
    public static function determine(string $stockType, string $unit, array|string|null $labels = null, ?string $pluginBilling = null): string
    {
        $codes = self::labelCodes($labels);

        if (\in_array(self::LABEL_RECURRING, $codes, true)) {
            return self::RECURRING;
        }

        if (\in_array(self::LABEL_ONE_TIME, $codes, true)) {
            return self::ONE_TIME;
        }

        if ($pluginBilling === self::RECURRING || $pluginBilling === self::ONE_TIME) {
            return $pluginBilling;
        }

        if ($stockType === 'typZasoby.sluzba') {
            return self::isPeriodUnit($unit) ? self::RECURRING : self::SERVICE;
        }

        return \in_array($stockType, self::ONE_TIME_TYPES, true) ? self::ONE_TIME : self::SERVICE;
    }

    /**
     * Is the unit a period of time (month, year, ...)?
     */
    public static function isPeriodUnit(string $unit): bool
    {
        return \in_array(mb_strtoupper(self::plainCode($unit)), self::PERIOD_UNITS, true);
    }

    /**
     * Unit code without the "code:" prefix.
     */
    public static function plainCode(string $code): string
    {
        return trim((string) preg_replace('/^code:/i', '', trim($code)));
    }

    /**
     * Short name of the kind for a badge.
     */
    public static function label(string $kind): string
    {
        return match ($kind) {
            self::RECURRING => _('Regularly invoiced service'),
            self::ONE_TIME => _('One-time purchase'),
            default => _('Service'),
        };
    }

    /**
     * What it means for the customer.
     *
     * @param string $unitName unit as the customer reads it ("month", "hour"), empty when unknown
     */
    public static function explanation(string $kind, string $unitName = ''): string
    {
        return match ($kind) {
            self::RECURRING => $unitName !== ''
                ? sprintf(_('You will be invoiced again for every %s until you end the contract. The price is for one %s.'), $unitName, $unitName)
                : _('You will be invoiced regularly until you end the contract.'),
            self::ONE_TIME => _('You pay once. Nothing is invoiced again afterwards.'),
            default => $unitName !== ''
                ? sprintf(_('A service invoiced by the unit (%s). How often it is invoiced is agreed when it is ordered.'), $unitName)
                : _('A service. How often it is invoiced is agreed when it is ordered.'),
        };
    }

    /**
     * Bootstrap colour of the badge.
     */
    public static function badgeClass(string $kind): string
    {
        return match ($kind) {
            self::RECURRING => 'bg-warning text-dark',
            self::ONE_TIME => 'bg-success',
            default => 'bg-info text-dark',
        };
    }

    /**
     * @param array<int, string>|string|null $labels
     *
     * @return array<int, string> upper case label codes without the "code:" prefix
     */
    private static function labelCodes(array|string|null $labels): array
    {
        $parts = \is_array($labels) ? $labels : explode(',', (string) $labels);

        return array_values(array_filter(array_map(
            static fn ($part): string => mb_strtoupper(self::plainCode((string) $part)),
            $parts,
        ), static fn (string $code): bool => $code !== ''));
    }
}
