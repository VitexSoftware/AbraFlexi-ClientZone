<?php

declare(strict_types=1);

/**
 * clientzone - Init aplikace.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2017-2026 VitexSoftware v.s.cz
 */

namespace ClientZone;

use Ease\Shared;

require_once __DIR__.'/../../vendor/autoload.php';

// Must use singleton — HtmlTag later calls Locale::singleton() and would
 // otherwise re-init from Accept-Language (en_US) and undo translations.
\Ease\Locale::singleton('cs_CZ', __DIR__.'/../../i18n', 'clientzone');

Shared::init(
    [
        'ABRAFLEXI_URL', 'ABRAFLEXI_LOGIN', 'ABRAFLEXI_PASSWORD', 'ABRAFLEXI_COMPANY',
        'ALLOW_REGISTER', 'SHOW_PRICELIST', 'PROD_CAT',
        'SEND_MAILS_FROM', 'SEND_INFO_TO', 'EMAIL_FROM', 'EASE_EMAILTO',
        'SUPPRESS_EMAILS', 'CONFIGURED', 'EASE_LOGGER', 'DEBUG',
    ],
    __DIR__.'/../../.env',
    false,
);

// Display name (package APP_NAME stays the composer id). Honour .env override.
if (Shared::cfg('EASE_APPNAME') === null || Shared::cfg('EASE_APPNAME') === '') {
    \define('EASE_APPNAME', _('Client Zone'));
}

if (\PHP_SAPI === 'cli') {
    if (!defined('EASE_LOGGER')) {
        define('EASE_LOGGER', 'syslog|console|email');
    }
} else {
    session_start();

    Shared::user(null, User::class);

    /* @var $oPage ui\WebPage */
    $oPage = new ui\WebPage();

    $inSetup = strstr(ui\WebPage::phpSelf(), 'setup.php');

    if (!$inSetup && empty(Shared::cfg('ABRAFLEXI_URL'))) {
        $oPage->redirect('setup.php');
        $oPage->draw();
        exit;
    }
}

/**
 * Objekt uživatele User nebo Anonym
 * @global User|\Ease\Anonym $oUser
 */
$oUser = Shared::user();
$shared = Shared::instanced();
