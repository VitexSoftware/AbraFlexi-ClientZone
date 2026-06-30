<?php

namespace ClientZone;

/**
 * clientzone - Stránka firmy/adresy.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017 VitexSoftware v.s.cz
 */
require_once 'includes/Init.php';

$oPage->onlyForLogged();

$address = Engine::doThings($oPage);
if (is_null($address)) {
    $address = new \AbraFlexi\Adresar($oUser->adresar->getRecordIdent(),
        ['defaultUrlParams' => ['relations' => 'kontakt']]);
}

if ($oPage->getGetValue('delete', 'bool') == 'true') {
    if ($address->delete()) {
        $oPage->redirect('addresar.php');
        exit;
    }
}

$oPage->addItem(new ui\PageTop(_('Address').': '.$address->getDataValue('nazev')));

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
