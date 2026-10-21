<?php

declare(strict_types=1);

namespace ClientZone;

/**
 * clientzone — Customer's issued invoices (faktura-vydana).
 *
 * @author     Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2026 VitexSoftware
 */
require_once 'includes/Init.php';

$oPage->onlyForCustomer();

$oPage->addToHeader(new ui\PageTop(_('My Invoices')));

\Ease\Part::jQueryze();
$oPage->container->addItem(new ui\DocumentDataTable(new InvoiceLister()));

$oPage->addItem(new ui\PageBottom());
$oPage->draw();
