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

use ClientZone\ProductDetail;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The product code goes into an AbraFlexi filter and only products of the shop may be shown.
 */
class ProductDetailTest extends TestCase
{
    /**
     * @return array<string, array{0: null|string, 1: string}>
     */
    public static function codes(): array
    {
        return [
            'plain' => ['MULTIFLEXI', 'MULTIFLEXI'],
            'with dash' => ['MFAPP-LITE', 'MFAPP-LITE'],
            'with space' => ['HOUSING DISK', 'HOUSING DISK'],
            'with underscore' => ['ABRAFLEXI_DIGEST', 'ABRAFLEXI_DIGEST'],
            'quote removed' => ["X' or kod like '%", 'X or kod like'],
            'brackets removed' => ['A) or (1=1', 'A or 11'],
            'null' => [null, ''],
            'only junk' => ["'\"();", ''],
        ];
    }

    #[DataProvider('codes')]
    public function testCodeIsReducedToWhatCodesAreMadeOf(?string $code, string $expected): void
    {
        $this->assertSame($expected, ProductDetail::sanitizeCode($code));
    }

    public function testCodeIsCutToTheMaximumLength(): void
    {
        $this->assertSame(ProductDetail::MAX_CODE_LENGTH, mb_strlen(ProductDetail::sanitizeCode(str_repeat('A', 500))));
    }

    public function testConditionFindsOnlyProductsOfTheShop(): void
    {
        $condition = ProductDetail::condition('MULTIFLEXI');

        $this->assertStringStartsWith("kod eq 'MULTIFLEXI'", $condition);
        $this->assertStringContainsString("stitky = 'code:ESHOP'", $condition);
        $this->assertStringContainsString("stitky = 'code:AKTIVNI'", $condition);
    }

    public function testVatPercentFromPrices(): void
    {
        $this->assertSame(21, ProductDetail::vatPercent(100.0, 121.0));
        $this->assertSame(21, ProductDetail::vatPercent(32.0, 38.72));
        $this->assertSame(12, ProductDetail::vatPercent(100.0, 112.0));
        $this->assertSame(0, ProductDetail::vatPercent(100.0, 100.0));
        $this->assertSame(0, ProductDetail::vatPercent(0.0, 0.0));
    }

    public function testPriceWithVatUsesTheRateOfTheItem(): void
    {
        // AbraFlexi delivered 32,00 as the price with VAT of an item entered without VAT
        $this->assertSame(38.72, ProductDetail::priceWithVat(32.0, 21.0, 32.0));
        $this->assertSame(36.0, ProductDetail::priceWithVat(32.0, 12.5, null));
        $this->assertSame(32.0, ProductDetail::priceWithVat(32.0, 0.0, null));
        $this->assertSame(40.0, ProductDetail::priceWithVat(32.0, 0.0, 40.0));
    }

    public function testStockTypeLabels(): void
    {
        $this->assertNotSame('', ProductDetail::stockTypeLabel('typZasoby.sluzba'));
        $this->assertNotSame('', ProductDetail::stockTypeLabel('typZasoby.zbozi'));
        $this->assertNotSame(ProductDetail::stockTypeLabel('typZasoby.sluzba'), ProductDetail::stockTypeLabel('typZasoby.zbozi'));
        $this->assertSame('', ProductDetail::stockTypeLabel('unknown'));
    }

    public function testPriceIsFormatted(): void
    {
        $this->assertSame('1 234,50 Kč', ProductDetail::formatPrice(1234.5));
        $this->assertSame('32,00 Kč', ProductDetail::formatPrice(32.0));
    }
}
