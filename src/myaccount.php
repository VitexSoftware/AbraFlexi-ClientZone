<?php

declare(strict_types=1);

namespace ClientZone;

/**
 * clientzone - Customer's own company / address page.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2026 VitexSoftware
 */
require_once 'includes/Init.php';

$oPage->onlyForCustomer();

$firma = $oUser->getAdresar()->getRecordIdent();
if (empty($firma)) {
    $oPage->addStatusMessage(_('Your company record was not found'), 'error');
    $oPage->redirect('dashboard.php');
    exit;
}

// Always the logged-in customer's company — never accept id from the request.
$address = new \AbraFlexi\Adresar(
    $firma,
    ['defaultUrlParams' => ['relations' => 'kontakt']],
);

$oPage->addToHeader(new ui\PageTop(_('Address').': '.$address->getDataValue('nazev')));

$addressRow = new \Ease\TWB5\Row();
$addressRow->addColumn(4, new ui\InvoicesOfAddresButton($address));

$headerRow = new \Ease\TWB5\Row();
$headerRow->addColumn(12,
    '<strong>'.$address->getDataValue('kod').' - '.$address->getDataValue('nazev').'</strong>');

$addressRow->addColumn(8,
    new \Ease\TWB5\Panel(
        $headerRow,
        'info',
        new ui\AddressForm($address),
        [new ui\AddressContactsLinks($address)],
    ));

$oPage->container->addItem($addressRow);
$oPage->addItem(new ui\PageBottom());
$oPage->draw();
