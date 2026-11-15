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
 * Where an issued invoice stands: how much is left to pay, whether it is overdue and which
 * reminders were sent for it.
 *
 * The dates of the reminders (datUp1, datUp2, datSmir) are filled by abraflexi-reminder when it sends
 * them. An invoice that was not reminded has them empty and nothing is shown for it.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
final class InvoiceStatus
{
    /**
     * Columns of the invoice the status needs.
     */
    public const COLUMNS = ['id', 'kod', 'datSplat', 'sumCelkem', 'zbyvaUhradit', 'stavUhrK', 'storno', 'mena', 'datUp1', 'datUp2', 'datSmir'];

    /**
     * Reminder dates by the level of the reminder.
     */
    public const REMINDER_COLUMNS = [1 => 'datUp1', 2 => 'datUp2', 3 => 'datSmir'];

    private function __construct()
    {
    }

    /**
     * Is there something left to pay?
     *
     * @param array<string, mixed> $invoice
     */
    public static function isUnpaid(array $invoice): bool
    {
        if (self::truthy($invoice['storno'] ?? false)) {
            return false;
        }

        $state = (string) ($invoice['stavUhrK'] ?? '');

        // not settled: no state yet, or a part of it is paid
        if ($state !== '' && $state !== 'stavUhr.castUhr') {
            return false;
        }

        return (float) ($invoice['zbyvaUhradit'] ?? 0) > 0.0;
    }

    /**
     * Days after the due date; 0 when the invoice is not due yet or has no due date.
     *
     * @param array<string, mixed> $invoice
     */
    public static function overdueDays(array $invoice, \DateTimeImmutable $today): int
    {
        $due = Customer::dateToIso($invoice['datSplat'] ?? null);

        if ($due === null) {
            return 0;
        }

        // calendar days: AbraFlexi dates carry an offset that must not shift the day
        $days = (int) (new \DateTimeImmutable($due))->diff($today->setTime(0, 0))->format('%r%a');

        return max(0, $days);
    }

    /**
     * Is the invoice unpaid and past its due date?
     *
     * @param array<string, mixed> $invoice
     */
    public static function isOverdue(array $invoice, \DateTimeImmutable $today): bool
    {
        return self::isUnpaid($invoice) && self::overdueDays($invoice, $today) > 0;
    }

    /**
     * Reminders that were sent, in the order of their level.
     *
     * @param array<string, mixed> $invoice
     *
     * @return array<int, array{level: int, date: string}> date as d.m.Y
     */
    public static function reminders(array $invoice): array
    {
        $sent = [];

        foreach (self::REMINDER_COLUMNS as $level => $column) {
            $date = Customer::formatDate($invoice[$column] ?? null);

            if ($date !== null) {
                $sent[] = ['level' => $level, 'date' => $date];
            }
        }

        return $sent;
    }

    /**
     * Name of the reminder of the level.
     */
    public static function reminderLabel(int $level): string
    {
        return match ($level) {
            1 => _('1st reminder'),
            2 => _('2nd reminder'),
            default => _('3rd reminder'),
        };
    }

    /**
     * @param mixed $value true/false, "true"/"false" as AbraFlexi writes them
     */
    private static function truthy(mixed $value): bool
    {
        return $value === true || (\is_string($value) && strtolower($value) === 'true');
    }
}
