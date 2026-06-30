<?php

declare(strict_types=1);

/**
 * clientzone - Admin User.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2024 VitexSoftware v.s.cz
 */

namespace ClientZone;

class User extends \Ease\User
{
    public string $myTable = 'users';
    public string $keyColumn = 'id';
    public ?string $loginColumn = 'login';
    public string $passwordColumn = 'password';
    public string $emailColumn = 'email';

    public function getSettingValue(string $settingName): ?string
    {
        $value = $this->getDataValue($settingName);
        return $value !== null ? (string) $value : null;
    }
}
