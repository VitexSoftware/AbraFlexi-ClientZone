<?php

declare(strict_types=1);

namespace ClientZone;

/**
 * clientzone — View a single invoice/order belonging to the logged-in customer.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2026 VitexSoftware v.s.cz
 */
require_once 'includes/Init.php';

$oPage->onlyForCustomer();

$invoiceID = $oPage->getRequestValue('id', 'int');

if (!$invoiceID) {
    $oPage->addStatusMessage(_('Wrong call'), 'error');
    $oPage->redirect('dashboard.php');
    exit;
}

$proforma = new \AbraFlexi\FakturaVydana((int) $invoiceID);

if (!GateKeeper::isAccessibleBy($proforma, $oUser)) {
    $oPage->addStatusMessage(_('Wrong call'), 'error');
    $oPage->redirect('dashboard.php');
    exit;
}

$oPage->addToHeader(new ui\PageTop(_('Order').' '.$proforma->getDataValue('kod')));

$oPage->addItem(
    new \Ease\TWB5\Container(
        new \Ease\TWB5\Panel(
            _('Order').' '.$proforma->getDataValue('kod'),
            'success',
            new ui\HtmlInvoice($proforma),
            new ui\DownloadInvoiceButton($proforma),
        ),
    ),
);

$oPage->addItem(new ui\PageBottom());
$oPage->draw();
