<?php

declare(strict_types=1);

namespace ClientZone;

/**
 * clientzone — Detail of customer's contract (smlouva) with its products.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2026 VitexSoftware
 */
require_once 'includes/Init.php';

$oPage->onlyForCustomer();

$id = $oPage->getRequestValue('id');

$contract = new \AbraFlexi\Smlouva(is_numeric($id) ? (int) $id : $id);

if (is_null($contract->getMyKey()) || !GateKeeper::isAccessibleBy($contract, $oUser)) {
    $oPage->addStatusMessage(_('Wrong call'), 'error');
    $oPage->redirect('myservices.php');
    $oPage->draw();

    exit;
}

$oPage->addToHeader(new ui\PageTop(_('Service').' '.$contract->getDataValue('kod')));

$details = new \Ease\Html\TableTag(null, ['class' => 'table']);

foreach ([
    'kod' => _('Contract number'),
    'nazev' => _('Name'),
    'popis' => _('Description'),
    'typSml' => _('Contract type'),
    'stavSml' => _('Status'),
    'smlouvaOd' => _('Valid from'),
    'smlouvaDo' => _('Valid to'),
    'datumPodepsani' => _('Signed on'),
    'frekFakt' => _('Billing frequency (months)'),
    'lastUpdate' => _('Last change'),
] as $field => $label) {
    $raw = $contract->getDataValue($field);

    $details->addRowColumns([
        '<strong>'.$label.'</strong>',
        \htmlspecialchars(ContractLister::displayValue($raw, $field === 'lastUpdate'), \ENT_QUOTES),
    ]);
}

$oPage->container->addItem(new \Ease\Html\H2Tag(_('Contract')));
$oPage->container->addItem($details);

$oPage->container->addItem(new \Ease\Html\H2Tag(_('Products on this contract')));

$products = new \AbraFlexi\SmlouvaPolozka();
$products->defaultUrlParams['limit'] = 0;
$rows = $products->getColumnsFromAbraFlexi(
    ['id', 'kod', 'nazev', 'mnozMj', 'cenaMj', 'cenaCelkem', 'platiOdData', 'platiDoData'],
    ['smlouva' => $contract->getMyKey()],
);

if (empty($rows)) {
    $oPage->addStatusMessage(_('This contract has no products yet.'), 'info');
} else {
    $listing = new \Ease\Html\TableTag(null, ['class' => 'table']);
    $listing->addRowHeaderColumns([
        _('Code'),
        _('Product'),
        _('Quantity'),
        _('Unit price'),
        _('Total'),
        _('Valid from'),
        _('Valid to'),
    ]);

    foreach ($rows as $row) {
        $listing->addRowColumns([
            \htmlspecialchars((string) ($row['kod'] ?? ''), \ENT_QUOTES),
            \htmlspecialchars((string) ($row['nazev'] ?? ''), \ENT_QUOTES),
            \htmlspecialchars((string) ($row['mnozMj'] ?? ''), \ENT_QUOTES),
            \htmlspecialchars((string) ($row['cenaMj'] ?? ''), \ENT_QUOTES),
            \htmlspecialchars((string) ($row['cenaCelkem'] ?? ''), \ENT_QUOTES),
            ContractLister::displayValue($row['platiOdData'] ?? null),
            ContractLister::displayValue($row['platiDoData'] ?? null),
        ]);
    }

    $oPage->container->addItem($listing);
}

$oPage->container->addItem(new \Ease\TWB5\LinkButton('myservices.php', '← '._('Back to my services'), 'outline-secondary', ['class' => 'mt-3']));

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
