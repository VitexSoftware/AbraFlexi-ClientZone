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

namespace Test\ClientZone\Contract;

use ClientZone\ContractWithdrawal;
use ClientZone\CsrfToken;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Decisions about withdrawal that need no AbraFlexi connection.
 */
class ContractWithdrawalTest extends TestCase
{
    private \DateTimeImmutable $today;

    protected function setUp(): void
    {
        $this->today = new \DateTimeImmutable('2026-11-14');
    }

    public function testNothingInvoicedAheadEndsToday(): void
    {
        $this->assertSame('2026-11-14', ContractWithdrawal::effectiveDate(null, $this->today)->format('Y-m-d'));
    }

    public function testInvoicedPeriodInTheFutureIsHonoured(): void
    {
        $this->assertSame('2026-11-30', ContractWithdrawal::effectiveDate('2026-11-30', $this->today)->format('Y-m-d'));
    }

    public function testInvoicedPeriodInThePastDoesNotMoveTheEndBack(): void
    {
        $this->assertSame('2026-11-14', ContractWithdrawal::effectiveDate('2026-10-31', $this->today)->format('Y-m-d'));
    }

    public function testItemEndingTodayAtOffsetMidnightIsStillActive(): void
    {
        $contract = (new \ReflectionClass(ContractWithdrawal::class))->newInstanceWithoutConstructor();
        (new \ReflectionProperty($contract, 'today'))->setValue($contract, $this->today);

        $this->assertTrue($contract->isActive(['platiDoData' => new \DateTimeImmutable('2026-11-14 00:00:00+01:00')]));
    }

    public function testPeriodEndingTodayEndsToday(): void
    {
        $this->assertSame('2026-11-14', ContractWithdrawal::effectiveDate('2026-11-14', $this->today)->format('Y-m-d'));
    }

    #[DataProvider('dayForms')]
    public function testDayIsRecognisedInEveryForm(mixed $value, ?string $expected): void
    {
        $day = ContractWithdrawal::toDay($value);
        $this->assertSame($expected, $day?->format('Y-m-d'));
    }

    /**
     * @return array<string, array{0: mixed, 1: ?string}>
     */
    public static function dayForms(): array
    {
        return [
            'plain date' => ['2026-11-30', '2026-11-30'],
            'AbraFlexi date with zone' => ['2026-11-30+01:00', '2026-11-30'],
            'datetime' => ['2026-11-30T10:20:30', '2026-11-30'],
            'DateTime object' => [new \DateTime('2026-11-30 12:00'), '2026-11-30'],
            'midnight with fixed offset' => [new \DateTimeImmutable('2026-11-14 00:00:00+01:00'), '2026-11-14'],
            'late evening in the west' => [new \DateTimeImmutable('2026-11-14 23:30:00-08:00'), '2026-11-14'],
            'empty string' => ['', null],
            'null' => [null, null],
            'false' => [false, null],
            'garbage' => ['soon', null],
        ];
    }

    /**
     * @param array<string, mixed> $item
     */
    #[DataProvider('items')]
    public function testItemIsWithdrawableUntilItsValidityEnds(array $item, bool $active): void
    {
        $contract = (new \ReflectionClass(ContractWithdrawal::class))->newInstanceWithoutConstructor();
        (new \ReflectionProperty($contract, 'today'))->setValue($contract, $this->today);

        $this->assertSame($active, $contract->isActive($item));
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: bool}>
     */
    public static function items(): array
    {
        return [
            'no end' => [['platiDoData' => null], true],
            'ends in the future' => [['platiDoData' => '2026-12-31'], true],
            'ends today' => [['platiDoData' => '2026-11-14'], true],
            'ended yesterday' => [['platiDoData' => '2026-11-13'], false],
            'end missing' => [[], true],
        ];
    }

    public function testCsrfTokenIsStableAndChecked(): void
    {
        $_SESSION = [];
        $token = CsrfToken::get();

        $this->assertSame(64, \strlen($token));
        $this->assertSame($token, CsrfToken::get());
        $this->assertTrue(CsrfToken::isValid($token));
        $this->assertFalse(CsrfToken::isValid('forged'));
        $this->assertFalse(CsrfToken::isValid(null));
        $this->assertFalse(CsrfToken::isValid(['array']));
    }

    public function testCsrfTokenIsRejectedWhenSessionHasNone(): void
    {
        $_SESSION = [];
        $this->assertFalse(CsrfToken::isValid(''));
        $this->assertFalse(CsrfToken::isValid('anything'));
    }
}
