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
 * Withdrawal of a customer from a contract, whole or only from some of its items.
 *
 * A withdrawn item gets its validity end (platiDoData) so the invoices AbraFlexi
 * generates from the contract afterwards no longer contain it. Whole withdrawal
 * also ends the contract itself (smlouvaDo).
 *
 * The validity ends with the period that has already been invoiced, because that
 * invoice is issued and the customer owes it; when nothing has been invoiced ahead
 * the item ends today.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class ContractWithdrawal
{
    /**
     * Item columns needed to decide and to show.
     */
    public const ITEM_COLUMNS = ['id', 'kod', 'nazev', 'mnozMj', 'cenaMj', 'cenaCelkem', 'platiOdData', 'platiDoData', 'datPoslFaktObd'];

    private \DateTimeImmutable $today;

    public function __construct(private \AbraFlexi\Smlouva $contract, ?\DateTimeImmutable $today = null)
    {
        $this->today = ($today ?? new \DateTimeImmutable('today'))->setTime(0, 0);
    }

    /**
     * Date as plain day, whatever form AbraFlexi or the library delivers it in.
     */
    public static function toDay(mixed $value): ?\DateTimeImmutable
    {
        if ($value === null || $value === false || $value === '') {
            return null;
        }

        if ($value instanceof \AbraFlexi\Date && $value->isNull) {
            return null;
        }

        // Compare calendar days only: AbraFlexi dates carry a fixed offset that would shift the day against "today"
        if ($value instanceof \DateTimeInterface) {
            return new \DateTimeImmutable($value->format('Y-m-d'));
        }

        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', (string) $value, $matches) === 1) {
            return new \DateTimeImmutable($matches[1]);
        }

        return null;
    }

    /**
     * Last day an item is still billed when the customer withdraws on the given day.
     *
     * @param mixed $billedUntil end of the last invoiced period of the item (datPoslFaktObd)
     */
    public static function effectiveDate(mixed $billedUntil, \DateTimeImmutable $today): \DateTimeImmutable
    {
        $billed = self::toDay($billedUntil);

        return $billed !== null && $billed > $today ? $billed : $today;
    }

    /**
     * Is the item still valid, so withdrawing from it makes sense?
     *
     * @param array<string, mixed> $item
     */
    public function isActive(array $item): bool
    {
        $until = self::toDay($item['platiDoData'] ?? null);

        return $until === null || $until >= $this->today;
    }

    /**
     * Items of the contract.
     *
     * @return array<int, array<string, mixed>> items keyed by id
     */
    public function items(): array
    {
        $products = new \AbraFlexi\SmlouvaPolozka();
        $products->defaultUrlParams['limit'] = 0;
        $rows = $products->getColumnsFromAbraFlexi(self::ITEM_COLUMNS, ['smlouva' => $this->contract->getMyKey()]);
        $items = [];

        foreach (\is_array($rows) ? $rows : [] as $row) {
            $items[(int) $row['id']] = $row;
        }

        return $items;
    }

    /**
     * Items of the contract that can still be withdrawn from.
     *
     * @return array<int, array<string, mixed>>
     */
    public function activeItems(): array
    {
        return array_filter($this->items(), fn (array $item): bool => $this->isActive($item));
    }

    /**
     * Withdraw from items, or from the whole contract.
     *
     * @param array<int, int|string> $itemIds ids of the items to withdraw from; ignored for a whole withdrawal
     * @param bool                   $whole   withdraw from all items and end the contract
     *
     * @return array<int, string> last billed day (Y-m-d) of every withdrawn item, keyed by item id
     *
     * @throws \InvalidArgumentException when nothing withdrawable was chosen
     * @throws \AbraFlexi\Exception      when AbraFlexi refuses the change
     */
    public function withdraw(array $itemIds, bool $whole = false): array
    {
        $active = $this->activeItems();
        $chosen = $whole ? array_keys($active) : array_values(array_filter(
            array_map('intval', $itemIds),
            static fn (int $id): bool => $id > 0,
        ));

        // Only items of this contract that are still valid: ids from the form are not trusted
        $chosen = array_values(array_intersect($chosen, array_keys($active)));

        if ($chosen === []) {
            throw new \InvalidArgumentException(_('Choose at least one product to withdraw from'));
        }

        $ends = [];
        $rows = [];

        foreach ($chosen as $id) {
            $day = self::effectiveDate($active[$id]['datPoslFaktObd'] ?? null, $this->today)->format('Y-m-d');
            $ends[$id] = $day;
            $rows[] = ['id' => $id, 'platiDoData' => $day];
        }

        $change = ['id' => $this->contract->getMyKey(), 'polozkySmlouvy' => $rows];

        if ($whole) {
            $change['smlouvaDo'] = max($ends);
        }

        $writer = new \AbraFlexi\Smlouva();

        if (!$writer->insertToAbraFlexi($change) || $writer->lastResponseCode !== 201) {
            throw new \AbraFlexi\Exception(_('The withdrawal was not saved'), $writer->lastResponseCode);
        }

        return $ends;
    }
}
