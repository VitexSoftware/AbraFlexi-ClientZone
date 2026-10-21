<?php

declare(strict_types=1);

namespace ClientZone;

/**
 * clientzone — Customer's received invoices (faktura-prijata).
 *
 * Shown only for companies that are both customer and supplier
 * (typVztahu.odberDodav).
 *
 * @author     Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2026 VitexSoftware
 */
require_once 'includes/Init.php';

$oPage->onlyForCustomer();

/** @var Customer $oUser */
if (!$oUser->isSupplierCustomer()) {
    $oPage->addStatusMessage(_('Received invoices are available only for supplier-customers'), 'warning');
    $oPage->redirect('dashboard.php');
    $oPage->draw();
    exit;
}

$oPage->addToHeader(new ui\PageTop(_('Received invoices')));

\Ease\Part::jQueryze();
$oPage->container->addItem(new ui\DocumentDataTable(new ReceivedInvoiceLister()));

$oPage->addItem(new ui\PageBottom());
$oPage->draw();
