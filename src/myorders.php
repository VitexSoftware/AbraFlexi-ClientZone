<?php

declare(strict_types=1);

namespace ClientZone;

/**
 * clientzone — Customer's received orders (objednávka-prijata).
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2026 VitexSoftware
 */
require_once 'includes/Init.php';

$oPage->onlyForLogged();

$oPage->addToHeader(new ui\PageTop(_('My Orders')));

\Ease\Part::jQueryze();
$oPage->container->addItem(new ui\DocumentDataTable(new OrderLister()));

$oPage->addItem(new ui\PageBottom());
$oPage->draw();
