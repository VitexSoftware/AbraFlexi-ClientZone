<?php

declare(strict_types=1);

namespace ClientZone;

/**
 * clientzone — Customer's subscribed services (smlouvy).
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2026 VitexSoftware
 */
require_once 'includes/Init.php';

$oPage->onlyForCustomer();

$oPage->addToHeader(new ui\PageTop(_('My services')));

\Ease\Part::jQueryze();
$oPage->container->addItem(new ui\DocumentDataTable(new ContractLister()));

$oPage->addItem(new ui\PageBottom());
$oPage->draw();
