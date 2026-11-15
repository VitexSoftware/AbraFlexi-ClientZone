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

namespace Test\ClientZone\Search;

use ClientZone\Searcher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The search term goes into an AbraFlexi filter: it must not be able to change the filter.
 */
class SearcherTest extends TestCase
{
    /**
     * @return array<string, array{0: null|string, 1: string}>
     */
    public static function terms(): array
    {
        return [
            'plain' => ['Multi', 'Multi'],
            'spaces collapsed' => ["  Multi \t Flexi  ", 'Multi Flexi'],
            'code characters kept' => ['VF2-0001/2026', 'VF2-0001/2026'],
            'diacritics kept' => ['Poštovní schránka', 'Poštovní schránka'],
            'quote removed' => ["x' or firma = 'code:OTHER", 'x or firma code OTHER'],
            'filter operators removed' => ['a) or (1=1', 'a or 1 1'],
            'empty' => ['', ''],
            'null' => [null, ''],
            'only junk' => ["'\"();", ''],
        ];
    }

    #[DataProvider('terms')]
    public function testTermIsReducedToHarmlessCharacters(?string $term, string $expected): void
    {
        $this->assertSame($expected, Searcher::sanitizeTerm($term));
    }

    public function testTermIsCutToTheMaximumLength(): void
    {
        $this->assertSame(Searcher::MAX_LENGTH, mb_strlen(Searcher::sanitizeTerm(str_repeat('a', 500))));
    }

    public function testShortTermIsNotSearched(): void
    {
        $this->assertFalse(Searcher::isSearchable(''));
        $this->assertFalse(Searcher::isSearchable('a'));
        $this->assertTrue(Searcher::isSearchable('ab'));
    }

    public function testLikeConditionSearchesEveryColumn(): void
    {
        $this->assertSame(
            "(kod like similar 'Multi' or nazev like similar 'Multi')",
            Searcher::likeCondition('Multi', ['kod', 'nazev']),
        );
    }

    public function testPricelistConditionOnlyFindsItemsSoldByTheShop(): void
    {
        $condition = Searcher::pricelistCondition('Multi');

        $this->assertStringContainsString("nazev like similar 'Multi'", $condition);
        $this->assertStringContainsString("stitky = 'code:ESHOP'", $condition);
        $this->assertStringContainsString("stitky = 'code:AKTIVNI'", $condition);
    }

    public function testDocumentConditionIsAlwaysLimitedToTheCompanyOfTheCustomer(): void
    {
        $condition = Searcher::documentCondition('VF2', 'faktura-vydana', "firma = 'code:POKUS'");

        $this->assertStringStartsWith("(firma = 'code:POKUS') and (", $condition);
        $this->assertStringContainsString("varSym like similar 'VF2'", $condition);
    }

    public function testDocumentConditionOfUnknownEvidenceSearchesTheCodeOnly(): void
    {
        $this->assertSame(
            "(firma = 'code:POKUS') and (kod like similar 'X1')",
            Searcher::documentCondition('X1', 'something-else', "firma = 'code:POKUS'"),
        );
    }

    public function testOrderLinkUsesThePluginOfTheProductOrTheCommonService(): void
    {
        $this->assertSame('MultiFlexi', Searcher::orderService('MULTIFLEXI', ['MULTIFLEXI' => 'MultiFlexi']));
        $this->assertSame('Common&kod=MFAPP-LITE', Searcher::orderService('MFAPP-LITE', ['MULTIFLEXI' => 'MultiFlexi']));
    }

    public function testCodePrefixIsRemoved(): void
    {
        $this->assertSame('MULTIFLEXI', Searcher::plainCode('code:MULTIFLEXI'));
        $this->assertSame('MULTIFLEXI', Searcher::plainCode(' MULTIFLEXI '));
    }
}
