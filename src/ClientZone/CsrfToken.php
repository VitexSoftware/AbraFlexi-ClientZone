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
 * Session bound token that protects forms with irreversible actions against forged requests.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
final class CsrfToken
{
    public const FIELD = 'csrf_token';

    private const SESSION_KEY = 'csrf_token';

    /**
     * Token of the current session, created on first use.
     */
    public static function get(): string
    {
        if (empty($_SESSION[self::SESSION_KEY]) || !\is_string($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Is the submitted token the one of this session?
     */
    public static function isValid(mixed $submitted): bool
    {
        $expected = $_SESSION[self::SESSION_KEY] ?? null;

        return \is_string($submitted) && \is_string($expected) && $expected !== '' && hash_equals($expected, $submitted);
    }
}
