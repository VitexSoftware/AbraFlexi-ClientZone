<?php

namespace ClientZone;

/**
 * clientzone - Přehled ceníku
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017 VitexSoftware v.s.cz
 */
require_once 'includes/Init.php';
$oPage->onlyForUser();

$id = $oPage->getRequestValue('id');

if (is_null($id)) {
    $oPage->addStatusMessage(_('Missing product ID'), 'error');
    $oPage->redirect('adminpricelist.php');
}

$item = new \AbraFlexi\Cenik(is_numeric($id) ? intval($id) : $id);



$oPage->addToHeader(new ui\PageTop($item->getDataValue('nazev')));

$oPage->container->addItem(new ui\ProductEditor($item));

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
