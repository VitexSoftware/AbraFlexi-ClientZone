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
$transaction_id = $oPage->getRequestValue('id', 'int');

$action = $oPage->getRequestValue('action');
switch ($action) {
    case 'settle':
        $invoice   = new FakturaVydana();
        $fID       = $oPage->getRequestValue('fakturaID');
        $sumCelkem = $oPage->getRequestValue('sumCelkem', 'float');
        $pokladna  = $oPage->getRequestValue('pokladna');
        if ($fID) {
            foreach ($fID as $fakturaID => $value) {
                $invoice->setId($fakturaID);
                if ($invoice->settle($value, $pokladna)) {
                    $invoice->addStatusMessage(sprintf(_('Faktura %s byla uhrazena'),
                            $fakturaID), 'success');
                    $sumCelkem -= $value;
                }
            }

            if ($sumCelkem) {
                //                $transaction = new PokladniPohyb($transaction_id);
//                if($transaction->saveToFlexiBee()){
//                    $transaction->addStatusMessage( sprintf(_('Přebytek vkladu %s byl uložen do pokladny kredit')));
//                }
                $invoice->addStatusMessage(sprintf(_('Vrať přeplatek %s'),
                        $sumCelkem), 'warning');
            }
        }
        break;
    default :
        $transaction = Engine::doThings($oPage);
        if (is_null($transaction)) {
            $transaction = new PokladniPohyb($transaction_id);
        }
        break;
}

$oPage->addToHeader(new ui\PageTop(_('Pokladna')));

$action = $oPage->getRequestValue('action');

$coder = new RadaPokladniPohyb();

switch ($action) {
    case 'settle':
        $transaction = new PokladniPohyb();
        $transaction->dataReset();
        $transaction->setDataValue('typPohybuK', 'typPohybu.prijem');
        $cashForm    = new ui\SettleForm($transaction);
        $cashForm->addSubmitSaveAndList();
        $cashForm->addSubmitSaveAndNext();
        $oPage->container->addItem(new \Ease\TWB5\Panel(_('Úhrada faktury'),
            'success', $cashForm, $coder->getNextRecordCode('SRO')));
        break;

    case 'income':
        $transaction->dataReset();
        $transaction->setDataValue('typPohybuK', 'typPohybu.prijem');
        $cashForm = new ui\CashForm($transaction);
        $cashForm->addSubmitSaveAndList();
        $cashForm->addSubmitSaveAndNext();
        $oPage->container->addItem(new \Ease\TWB5\Panel(_('Příjem'), 'success',
            $cashForm, $coder->getNextRecordCode('SRO')));
        break;
    case 'outcome':
        $transaction->dataReset();
        $transaction->setDataValue('sumCelkem', '-');
        $transaction->setDataValue('typPohybuK', 'typPohybu.vydej');
        $cashForm = new ui\CashForm($transaction);
        $cashForm->addSubmitSaveAndList();
        $cashForm->addSubmitSaveAndNext();
        $oPage->container->addItem(new \Ease\TWB5\Panel(_('Výdej'), 'danger',
            $cashForm, $coder->getNextRecordCode('SRO')));
        break;

    default:

        $cashTabs = new \Ease\TWB5\Tabs('cashTabs');
        $opTab    = $cashTabs->addTab(_('Operace Pokladny'));

        $mainMenu = $opTab->addItem(new ui\MainPageMenu());

        $mainMenu->addMenuItem(
            'images/settle.png', _('Úhrada faktury'), '?action=settle'
        );

        $mainMenu->addMenuItem(
            'images/income.png', _('Vklad'), '?action=income'
        );
        $mainMenu->addMenuItem(
            'images/outcome.png', _('Výdej'), '?action=outcome'
        );

        $listTab = $cashTabs->addTab(_('Přehled'));
        $listTab->addItem(new DataGrid(_('Transakce'), new PokladniPohyb()));

        $oPage->container->addItem($cashTabs);

        break;
}

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
