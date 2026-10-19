<?php

declare(strict_types=1);

/**
 * clientzone - Init aplikace.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2017-2024 VitexSoftware v.s.cz
 */

namespace ClientZone;

use Ease\Shared;

$autoloadCandidates = [
    __DIR__.'/../autoload.php',                         // Debian package
    __DIR__.'/../../vendor/autoload.php',               // composer checkout
    '/usr/share/php/abraflexi-clientzone/autoload.php', // legacy alias
];
foreach ($autoloadCandidates as $autoload) {
    if (is_readable($autoload)) {
        require_once $autoload;
        break;
    }
}

// Optional Debian packages used by VoIP / Subreg order plugins
foreach ([
    '/usr/share/php/IPEXB2B/autoload.php',
    '/usr/share/php/Subreg/autoload.php',
] as $depAutoload) {
    if (is_readable($depAutoload)) {
        require_once $depAutoload;
    }
}

$i18n = is_dir('/usr/share/locale') && !is_dir(__DIR__.'/../../i18n')
    ? '/usr/share/locale'
    : __DIR__.'/../../i18n';
new \Ease\Locale('cs_CZ', $i18n, 'clientzone');

$envCandidates = [
    '/etc/abraflexi-client-zone/.env',
    '/etc/abraflexi/clientzone.env',
    \dirname(__DIR__, 2).'/.env',
];
$envFile = \dirname(__DIR__, 2).'/.env';
foreach ($envCandidates as $candidate) {
    if (is_readable($candidate)) {
        $envFile = $candidate;
        break;
    }
}

Shared::init(
    [
        'ABRAFLEXI_URL', 'ABRAFLEXI_LOGIN', 'ABRAFLEXI_PASSWORD', 'ABRAFLEXI_COMPANY',
        'ABRAFLEXI_PROFORMA_DOCTYPE', 'ABRAFLEXI_BANK_DOCTYPE', 'ABRAFLEXI_STORAGE',
        'IPEX_URL', 'IPEX_LOGIN', 'IPEX_PASSWORD',
        'SUBREG_LOCATION', 'SUBREG_URI', 'SUBREG_LOGIN', 'SUBREG_PASSWORD',
        'ALLOW_REGISTER', 'SHOW_PRICELIST', 'PROD_CAT',
        'SEND_MAILS_FROM', 'SEND_INFO_TO', 'EMAIL_FROM', 'EASE_EMAILTO',
        'SUPPRESS_EMAILS', 'CONFIGURED', 'EASE_LOGGER', 'DEBUG',
    ],
    $envFile,
    false,
);

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
