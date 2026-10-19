<?php

namespace ClientZone;

/**
 * clientzone - Hlavní strana.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017 VitexSoftware v.s.cz
 */
require_once 'includes/Init.php';

$oPage->onlyForLogged();

$oPage->addToHeader(new ui\PageTop(_('My Orders')));

$evidencies = [
    'faktura-vydana' => _('Invoices'),
    'objednavka-prijata' => _('Orders'),
];
$fetcher = new \AbraFlexi\RO();
$firma = $oUser->getAdresar();
foreach ($evidencies as $evidence => $evCaption) {
    $fetcher->setEvidence($evidence);
    $oPage->container->addItem(new ui\OrdersListing(
        $fetcher,
        $evCaption,
        ['firma' => $firma],
    ));
}

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
