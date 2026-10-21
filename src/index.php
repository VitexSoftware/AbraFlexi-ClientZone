<?php

declare(strict_types=1);

namespace ClientZone;

/**
 * clientzone - Hlavní strana (e-shop).
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2026 VitexSoftware v.s.cz
 */
require_once 'includes/Init.php';

$oPage->addToHeader(new ui\PageTop(\Ease\Shared::appName()));

// Marketing hero for guests only — customers use dashboard.php after login.
if (!$oUser instanceof Customer) {
    $oPage->container->addItem(new ui\ShopHero());
}

if ($shared->getConfigValue('SHOW_PRICELIST') == 'true') {
    $oPage->container->addItem(new ui\Pricelist(
        (int) ($shared->getConfigValue('PROD_CAT') ?: 1),
        ['ESHOP', 'AKTIVNI'],
    ));
}

$oPage->addItem(new ui\PageBottom());
$oPage->draw();
