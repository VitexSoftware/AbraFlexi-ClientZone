<?php

namespace ClientZone;

/**
 * clientzone
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017 VitexSoftware v.s.cz
 */
require_once 'includes/Init.php';
$oPage->onlyForLogged();

$invoiceID = $oPage->getRequestValue('id', 'int');

if ($invoiceID) {
    $proforma = new \AbraFlexi\FakturaVydana($invoiceID);

    $oPage->addItem(new ui\PageTop(_('Order').' '.$proforma->getDataValue('kod')));


    $oPage->addItem(
        new \Ease\TWB5\Container(
        new \Ease\TWB5\Panel(_('Order').' '.$proforma->getDataValue('kod'),
        'success', new ui\HtmlInvoice($proforma),
        new \Ease\TWB5\LinkButton('getpdf.php?id='.$invoiceID,
        '<i class="fa fa-download"></i>'.' '.sprintf(_('Download Your Proforma #%s Here'),
            $proforma->getDataValue('kod')), 'success')
    )));
}
$oPage->addItem(new ui\PageBottom());

$oPage->draw();
