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

namespace Test\ClientZone\Invoice;

use ClientZone\InvoiceStatus;
use PHPUnit\Framework\TestCase;

/**
 * Overdue invoices show the reminders that were sent; nothing is made up for the others.
 */
class InvoiceStatusTest extends TestCase
{
    private function today(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2026-12-10');
    }

    /**
     * @param array<string, mixed> $extra
     *
     * @return array<string, mixed>
     */
    private function invoice(array $extra = []): array
    {
        return array_merge([
            'datSplat' => '2026-11-29+01:00',
            'zbyvaUhradit' => '3800.0',
            'stavUhrK' => '',
            'storno' => 'false',
            'datUp1' => '',
            'datUp2' => '',
            'datSmir' => '',
        ], $extra);
    }

    public function testUnpaidInvoicePastTheDueDateIsOverdue(): void
    {
        $this->assertTrue(InvoiceStatus::isOverdue($this->invoice(), $this->today()));
        $this->assertSame(11, InvoiceStatus::overdueDays($this->invoice(), $this->today()));
    }

    public function testInvoiceNotDueYetIsNotOverdue(): void
    {
        $invoice = $this->invoice(['datSplat' => '2026-12-24+01:00']);

        $this->assertFalse(InvoiceStatus::isOverdue($invoice, $this->today()));
        $this->assertSame(0, InvoiceStatus::overdueDays($invoice, $this->today()));
    }

    public function testInvoiceOnTheDueDateIsNotOverdueYet(): void
    {
        $this->assertSame(0, InvoiceStatus::overdueDays($this->invoice(['datSplat' => '2026-12-10+01:00']), $this->today()));
    }

    public function testPaidOrCancelledInvoiceIsNeverOverdue(): void
    {
        $this->assertFalse(InvoiceStatus::isOverdue($this->invoice(['stavUhrK' => 'stavUhr.uhrazenoRucne', 'zbyvaUhradit' => '0.0']), $this->today()));
        $this->assertFalse(InvoiceStatus::isOverdue($this->invoice(['zbyvaUhradit' => '0.0']), $this->today()));
        $this->assertFalse(InvoiceStatus::isOverdue($this->invoice(['storno' => 'true']), $this->today()));
        $this->assertFalse(InvoiceStatus::isOverdue($this->invoice(['storno' => true]), $this->today()));
    }

    public function testPartlyPaidInvoiceIsStillUnpaid(): void
    {
        $this->assertTrue(InvoiceStatus::isUnpaid($this->invoice(['stavUhrK' => 'stavUhr.castUhr', 'zbyvaUhradit' => '1000.0'])));
    }

    public function testInvoiceWithoutDueDateIsNotOverdue(): void
    {
        $this->assertFalse(InvoiceStatus::isOverdue($this->invoice(['datSplat' => '']), $this->today()));
    }

    public function testInvoiceThatWasNotRemindedHasNoReminders(): void
    {
        $this->assertSame([], InvoiceStatus::reminders($this->invoice()));
        // the empty date of AbraFlexi (today's date and the flag isNull) is no reminder
        $this->assertSame([], InvoiceStatus::reminders($this->invoice(['datUp1' => new \AbraFlexi\Date(''), 'datUp2' => new \AbraFlexi\Date(''), 'datSmir' => new \AbraFlexi\Date('')])));
    }

    public function testSentRemindersAreListedByLevel(): void
    {
        $reminders = InvoiceStatus::reminders($this->invoice(['datUp1' => '2026-12-02+01:00', 'datUp2' => new \AbraFlexi\Date('2026-12-09+01:00')]));

        $this->assertSame([['level' => 1, 'date' => '02.12.2026'], ['level' => 2, 'date' => '09.12.2026']], $reminders);
    }

    public function testSecondReminderWithoutTheFirstIsStillShown(): void
    {
        $this->assertSame([['level' => 2, 'date' => '09.12.2026']], InvoiceStatus::reminders($this->invoice(['datUp2' => '2026-12-09+01:00'])));
    }

    public function testEveryLevelHasALabel(): void
    {
        $this->assertNotSame('', InvoiceStatus::reminderLabel(1));
        $this->assertNotSame(InvoiceStatus::reminderLabel(1), InvoiceStatus::reminderLabel(2));
        $this->assertNotSame(InvoiceStatus::reminderLabel(2), InvoiceStatus::reminderLabel(3));
    }
}
