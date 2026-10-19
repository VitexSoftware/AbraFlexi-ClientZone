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

$autoloadCandidates = [
    __DIR__.'/../autoload.php',                         // Debian package
    __DIR__.'/../../vendor/autoload.php',               // composer checkout
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

// Packaged install lives under /usr/share/abraflexi-clientzone — never probe ../../i18n (open_basedir).
$packaged = str_starts_with(__DIR__, '/usr/share/abraflexi-clientzone');
$i18n = $packaged ? '/usr/share/locale' : \dirname(__DIR__, 2).'/i18n';
new \Ease\Locale('cs_CZ', $i18n, 'clientzone');

// Config: packaged path first, then legacy, then project checkout
if ($packaged) {
    $envFile = '/etc/abraflexi-client-zone/.env';
    if (!is_readable($envFile) && is_readable('/etc/abraflexi/clientzone.env')) {
        $envFile = '/etc/abraflexi/clientzone.env';
    }
} else {
    $envFile = \dirname(__DIR__, 2).'/.env';
}

Shared::init(
    [
        'ABRAFLEXI_URL', 'ABRAFLEXI_LOGIN', 'ABRAFLEXI_PASSWORD', 'ABRAFLEXI_COMPANY',
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
