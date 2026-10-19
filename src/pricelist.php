<?php

declare(strict_types=1);

namespace ClientZone;

/**
 * clientzone - Přehled ceníku / katalog.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2026 VitexSoftware v.s.cz
 */
require_once 'includes/Init.php';

$oPage->addToHeader(new ui\PageTop(_('Catalog')));
$oPage->container->addItem(new ui\Pricelist(
    (int) ($shared->getConfigValue('PROD_CAT') ?: 1),
    ['ESHOP', 'AKTIVNI'],
));
$oPage->addItem(new ui\PageBottom());
$oPage->draw();
