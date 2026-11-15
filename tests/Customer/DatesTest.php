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

namespace Test\ClientZone\Customer;

use ClientZone\Customer;
use PHPUnit\Framework\TestCase;

/**
 * A date AbraFlexi does not have must stay empty on the page: an unpaid invoice has no reminders yet.
 */
class DatesTest extends TestCase
{
    public function testEmptyValuesAreNoDate(): void
    {
        $this->assertNull(Customer::dateToIso(null));
        $this->assertNull(Customer::dateToIso(''));
        $this->assertNull(Customer::dateToIso(false));
        $this->assertNull(Customer::dateToIso('null'));
        $this->assertNull(Customer::formatDate(''));
    }

    public function testEmptyDateObjectOfAbraFlexiIsNoDate(): void
    {
        // AbraFlexi delivers an empty date as an object holding today's date and the flag isNull
        $empty = new \AbraFlexi\Date('');

        $this->assertTrue($empty->isNull);
        $this->assertNull(Customer::dateToIso($empty));
        $this->assertNull(Customer::formatDate($empty));
    }

    public function testRealDatesAreFormatted(): void
    {
        $this->assertSame('2026-11-29', Customer::dateToIso('2026-11-29+01:00'));
        $this->assertSame('2026-11-29', Customer::dateToIso(new \DateTimeImmutable('2026-11-29')));
        $this->assertSame('2026-11-29', Customer::dateToIso(new \AbraFlexi\Date('2026-11-29+01:00')));
        $this->assertSame('29.11.2026', Customer::formatDate(new \AbraFlexi\Date('2026-11-29+01:00')));
    }
}
