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

namespace Test\ClientZone\Supplier;

use ClientZone\Supplier;
use PHPUnit\Framework\TestCase;

/**
 * Supplier details: AbraFlexi wins, the configuration fills the gaps.
 */
class SupplierTest extends TestCase
{
    public function testConfigurationIsUsedWhenAbraFlexiProvidesNothing(): void
    {
        $supplier = new Supplier([], ['SUPPLIER_NAME' => 'Vitex', 'SUPPLIER_EMAIL' => 'finance@example.org', 'SUPPLIER_IC' => '69438676']);

        $this->assertSame('Vitex', $supplier->get('name'));
        $this->assertSame('finance@example.org', $supplier->get('email'));
        $this->assertSame('config', $supplier->source('email'));
        $this->assertTrue($supplier->hasEmail());
    }

    public function testAbraFlexiWinsOverConfiguration(): void
    {
        $supplier = new Supplier(
            [['nazFirmy' => 'From AbraFlexi', 'email' => 'abra@example.org']],
            ['SUPPLIER_NAME' => 'From config', 'SUPPLIER_EMAIL' => 'config@example.org'],
        );

        $this->assertSame('From AbraFlexi', $supplier->get('name'));
        $this->assertSame('abra@example.org', $supplier->get('email'));
        $this->assertSame('abraflexi', $supplier->source('email'));
    }

    public function testConfigurationFillsOnlyWhatAbraFlexiLacks(): void
    {
        $supplier = new Supplier(
            [['nazFirmy' => 'From AbraFlexi', 'email' => '']],
            ['SUPPLIER_NAME' => 'From config', 'SUPPLIER_EMAIL' => 'config@example.org'],
        );

        $this->assertSame('From AbraFlexi', $supplier->get('name'));
        $this->assertSame('config@example.org', $supplier->get('email'));
        $this->assertSame('config', $supplier->source('email'));
    }

    public function testBlankValuesCountAsMissing(): void
    {
        $supplier = new Supplier([['email' => '   ']], ['SUPPLIER_EMAIL' => "  \t"]);

        $this->assertSame('', $supplier->get('email'));
        $this->assertNull($supplier->source('email'));
        $this->assertFalse($supplier->hasEmail());
    }

    public function testSeveralSettingsRecordsAreSearchedInOrder(): void
    {
        $supplier = new Supplier([['email' => ''], ['email' => 'second@example.org'], ['email' => 'third@example.org']]);

        $this->assertSame('second@example.org', $supplier->get('email'));
    }

    public function testPhoneFallsBackToMobile(): void
    {
        $this->assertSame('+420 608', (new Supplier([['tel' => '', 'mobil' => '+420 608']]))->get('phone'));
        $this->assertSame('+420 233', (new Supplier([['tel' => '+420 233', 'mobil' => '+420 608']]))->get('phone'));
    }

    public function testStreetUsesEitherColumn(): void
    {
        $this->assertSame('Main 1', (new Supplier([['uliceNazev' => 'Main 1']]))->get('street'));
    }

    public function testNothingKnownGivesEmptyDetails(): void
    {
        $supplier = new Supplier();

        foreach (array_keys(Supplier::FIELDS) as $detail) {
            $this->assertSame('', $supplier->get($detail), $detail);
        }

        $this->assertSame('', $supplier->contactLine());
        $this->assertSame('', $supplier->get('unknown-detail'));
    }

    public function testContactLineJoinsWhatIsKnown(): void
    {
        $supplier = new Supplier([], ['SUPPLIER_EMAIL' => 'a@example.org', 'SUPPLIER_WEB' => 'example.org']);

        $this->assertSame('a@example.org, example.org', $supplier->contactLine());
    }

    public function testNonTextValuesFromAbraFlexiAreIgnored(): void
    {
        $supplier = new Supplier([['email' => ['not', 'text']]], ['SUPPLIER_EMAIL' => 'config@example.org']);

        $this->assertSame('config@example.org', $supplier->get('email'));
    }
}
