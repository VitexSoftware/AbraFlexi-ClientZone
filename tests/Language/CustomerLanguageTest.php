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

namespace Test\ClientZone\Language;

use ClientZone\CustomerLanguage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The language chosen in ClientZone is kept as a label of the company.
 */
class CustomerLanguageTest extends TestCase
{
    /**
     * @return array<string, array{0: null|string, 1: string}>
     */
    public static function locales(): array
    {
        return [
            'czech' => ['cs_CZ', 'cs'],
            'english' => ['en_US', 'en'],
            'with charset' => ['en_US.UTF-8', 'en'],
            'dash' => ['sk-SK', 'sk'],
            'bare language' => ['de', 'de'],
            'upper case' => ['CS_cz', 'cs'],
            'nothing' => [null, 'en'],
            'empty' => ['', 'en'],
            'nonsense' => ['xx-yy-zz', 'en'],
        ];
    }

    #[DataProvider('locales')]
    public function testLanguageOfLocale(?string $locale, string $expected): void
    {
        $this->assertSame($expected, CustomerLanguage::fromLocale($locale));
    }

    public function testLabelOfLanguage(): void
    {
        $this->assertSame('LANG_CS', CustomerLanguage::label('cs'));
        $this->assertSame('LANG_CS', CustomerLanguage::label('cs_CZ'));
    }

    public function testLanguageIsFoundAmongLabels(): void
    {
        $this->assertSame('cs', CustomerLanguage::fromLabels(['ESHOP', 'LANG_CS']));
        $this->assertSame('en', CustomerLanguage::fromLabels('ESHOP, code:LANG_EN, VIP'));
        $this->assertNull(CustomerLanguage::fromLabels(['ESHOP', 'VIP']));
        $this->assertNull(CustomerLanguage::fromLabels(null));
        $this->assertNull(CustomerLanguage::fromLabels('LANGUAGE_CS'));
    }

    public function testSettingLanguageKeepsOtherLabelsAndReplacesTheOldLanguage(): void
    {
        $this->assertSame(['ESHOP', 'LANG_EN'], CustomerLanguage::withLanguage(['ESHOP', 'LANG_CS'], 'en'));
        $this->assertSame(['LANG_CS'], CustomerLanguage::withLanguage(null, 'cs'));
        $this->assertSame(['VIP', 'LANG_CS'], CustomerLanguage::withLanguage('VIP, code:LANG_CS', 'cs'));
    }
}
