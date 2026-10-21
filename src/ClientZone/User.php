<?php

declare(strict_types=1);

/**
 * clientzone — Deprecated local admin user (removed).
 *
 * ClientZone no longer has an in-app administrator role. Privileged
 * configuration is done via SSH (.env) or directly in AbraFlexi.
 * This stub remains only so old sessions / autoload references do not fatal;
 * it can never authenticate and never gains document access.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2026 VitexSoftware v.s.cz
 *
 * @deprecated since 2.1 Use Customer only. Do not create local admin accounts.
 */

namespace ClientZone;

/**
 * @deprecated
 */
class User extends \Ease\User
{
    public function tryToLogin($formData = []): bool
    {
        $this->addStatusMessage(
            _('Local administrator accounts are no longer supported'),
            'error',
        );

        return false;
    }

    public function passwordCheck($plaintext): bool
    {
        return false;
    }

    public function getSettingValue(string $settingName): ?string
    {
        return null;
    }
}
