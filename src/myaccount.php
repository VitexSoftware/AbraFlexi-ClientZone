<?php

declare(strict_types=1);

namespace ClientZone;

/**
 * clientzone - Stránka firmy/adresy.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2026 VitexSoftware
 */
require_once 'includes/Init.php';

$oPage->onlyForLogged();

$address = Engine::doThings($oPage);
if ($address === null) {
    if (!$oUser instanceof Customer) {
        $oPage->addStatusMessage(_('Please sign in with your customer account'), 'warning');
        $oPage->redirect('login.php?backurl='.urlencode($_SERVER['REQUEST_URI'] ?? 'myaccount.php'));
        exit;
    }
    $firma = $oUser->getAdresar()->getRecordIdent();
    if (empty($firma)) {
        $oPage->addStatusMessage(_('Your company record was not found'), 'error');
        $oPage->redirect('index.php');
        exit;
    }
    $address = new \AbraFlexi\Adresar(
        $firma,
        ['defaultUrlParams' => ['relations' => 'kontakt']],
    );
}

if ($oPage->getGetValue('delete', 'bool') == 'true') {
    if ($address->delete()) {
        $oPage->redirect('addresar.php');
        exit;
    }
}

$oPage->addToHeader(new ui\PageTop(_('Address').': '.$address->getDataValue('nazev')));

$addressRow = new \Ease\TWB5\Row();
$infoColumn = $addressRow->addColumn(4,
    [/* new ui\ZewlScoreLabel($address), */new ui\InvoicesOfAddresButton($address)]);


switch ($oPage->getRequestValue('action')) {
    case 'delete':

        $confirmBlock = new \Ease\TWB5\Card();

        $confirmBlock->addItem($address);

        $confirmator = $confirmBlock->addItem(new \Ease\TWB5\Panel(_('Delete ?')),
            'danger');
        $confirmator->addItem(new \Ease\TWB5\LinkButton('addresar.php?id='.$address->getId(),
            _('No').' '.'<i class="fa fa-check"></i>', 'success'));
        $confirmator->addItem(new \Ease\TWB5\LinkButton('?delete=true&'.$address->keyColumn.'='.$address->getID(),
            _('Yes').' '.'<i class="fa fa-times"></i>', 'danger'));

        $headerRow = new \Ease\TWB5\Row();
        $headerRow->addColumn(8,
            '<strong>'.$address->getContactName().'</strong>');
        $headerRow->addColumn(4,
            new ui\LinkToFlexiBeeButton($address, ['style' => 'width: 20px']));
        $addressRow->addColumn(8,
            new \Ease\TWB5\Panel($headerRow, 'info', $confirmBlock));
        break;
    default :

//        $operationsMenu = $address->operationsMenu();
//        $operationsMenu->setTagCss(['float' => 'right']);
//        $operationsMenu->dropdown->addTagClass('pull-right');
        $operationsMenu = [];

        $headerRow = new \Ease\TWB5\Row();
        $headerRow->addColumn(8,
            '<strong>'.$address->getDataValue('kod').' - '.$address->getDataValue('nazev').'</strong>');
        $headerRow->addColumn(4,
            new ui\LinkToFlexiBeeButton($address, ['style' => 'width: 20px']));

        $addressRow->addColumn(8,
            new \Ease\TWB5\Panel($headerRow, 'info',
            new ui\AddressForm($address),
            [new ui\AddressContactsLinks($address)]));



        break;
}




$oPage->container->addItem($addressRow);


$oPage->addItem(new ui\PageBottom());

$oPage->draw();
