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

require_once '../vendor/autoload.php';

new \Ease\Locale('cs_CZ', '../i18n', 'clientzone');

Shared::init(
    ['ABRAFLEXI_URL', 'ABRAFLEXI_LOGIN', 'ABRAFLEXI_PASSWORD', 'ABRAFLEXI_COMPANY'],
    file_exists('/etc/abraflexi/clientzone.env')
        ? '/etc/abraflexi/clientzone.env'
        : \dirname(__DIR__, 2).'/.env',
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
