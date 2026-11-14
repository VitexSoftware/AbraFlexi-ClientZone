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

namespace Test\ClientZone\Registration;

use ClientZone\Registration;
use PHPUnit\Framework\TestCase;

/**
 * A confirmation link used twice must lead to the sign in, not to an error.
 */
class ConfirmationTest extends TestCase
{
    public function testContactWithTheLoginFromTheLinkIsAlreadyConfirmed(): void
    {
        $this->assertTrue(Registration::loginMatches(['username' => 'vitezslav.dvorak@spojenet.cz'], 'vitezslav.dvorak@spojenet.cz'));
        $this->assertTrue(Registration::loginMatches(['username' => 'User@Example.org '], ' user@example.org'));
    }

    public function testOtherLoginOrNoContactIsNotConfirmed(): void
    {
        $this->assertFalse(Registration::loginMatches(['username' => 'someone@example.org'], 'vitezslav.dvorak@spojenet.cz'));
        $this->assertFalse(Registration::loginMatches(['username' => ''], ''));
        $this->assertFalse(Registration::loginMatches(null, 'vitezslav.dvorak@spojenet.cz'));
        $this->assertFalse(Registration::loginMatches([], 'vitezslav.dvorak@spojenet.cz'));
    }
}
