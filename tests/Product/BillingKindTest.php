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

namespace Test\ClientZone\Product;

use ClientZone\BillingKind;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * One-time purchase or regularly invoiced service: the customer decides on it.
 */
class BillingKindTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string, 2: null|array<int, string>|string, 3: null|string, 4: string}>
     */
    public static function products(): array
    {
        return [
            'monthly service' => ['typZasoby.sluzba', 'code:MESIC', null, null, BillingKind::RECURRING],
            'yearly service' => ['typZasoby.sluzba', 'ROK', null, null, BillingKind::RECURRING],
            'unit written as in the catalog' => ['typZasoby.sluzba', 'code:MES', null, null, BillingKind::RECURRING],
            'training by the hour is not recurring' => ['typZasoby.sluzba', 'code:H', null, null, BillingKind::SERVICE],
            'service by gigabytes is only a service' => ['typZasoby.sluzba', 'code:GB', null, null, BillingKind::SERVICE],
            'service without unit' => ['typZasoby.sluzba', '', null, null, BillingKind::SERVICE],
            'goods' => ['typZasoby.zbozi', 'code:KS', null, null, BillingKind::ONE_TIME],
            'material' => ['typZasoby.material', '', null, null, BillingKind::ONE_TIME],
            'fee' => ['typZasoby.poplatek', '', null, null, BillingKind::ONE_TIME],
            'label wins over the unit' => ['typZasoby.sluzba', 'code:H', ['ESHOP', 'RECURRING'], null, BillingKind::RECURRING],
            'label wins over the type' => ['typZasoby.zbozi', 'code:KS', 'ESHOP, code:RECURRING', null, BillingKind::RECURRING],
            'one-time label on a monthly service' => ['typZasoby.sluzba', 'code:MESIC', ['ONETIME'], null, BillingKind::ONE_TIME],
            'plugin says recurring (it sells a fee)' => ['typZasoby.poplatek', '', null, BillingKind::RECURRING, BillingKind::RECURRING],
            'plugin says one-time (a credit)' => ['typZasoby.sluzba', 'code:MESIC', null, BillingKind::ONE_TIME, BillingKind::ONE_TIME],
            'label wins over the plugin' => ['typZasoby.sluzba', '', ['ONETIME'], BillingKind::RECURRING, BillingKind::ONE_TIME],
            'unknown type' => ['', '', null, null, BillingKind::SERVICE],
        ];
    }

    /**
     * @param null|array<int, string>|string $labels
     */
    #[DataProvider('products')]
    public function testKindOfProduct(string $stockType, string $unit, array|string|null $labels, ?string $plugin, string $expected): void
    {
        $this->assertSame($expected, BillingKind::determine($stockType, $unit, $labels, $plugin));
    }

    public function testPeriodUnits(): void
    {
        $this->assertTrue(BillingKind::isPeriodUnit('code:MESIC'));
        $this->assertTrue(BillingKind::isPeriodUnit('mes'));
        $this->assertFalse(BillingKind::isPeriodUnit('code:H'));
        $this->assertFalse(BillingKind::isPeriodUnit(''));
    }

    public function testEveryKindHasLabelExplanationAndBadge(): void
    {
        foreach ([BillingKind::ONE_TIME, BillingKind::RECURRING, BillingKind::SERVICE] as $kind) {
            $this->assertNotSame('', BillingKind::label($kind));
            $this->assertNotSame('', BillingKind::explanation($kind));
            $this->assertNotSame('', BillingKind::explanation($kind, 'month'));
            $this->assertStringStartsWith('bg-', BillingKind::badgeClass($kind));
        }

        $this->assertStringContainsString('month', BillingKind::explanation(BillingKind::RECURRING, 'month'));
    }
}
