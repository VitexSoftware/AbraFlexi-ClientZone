<?php

declare(strict_types=1);

namespace ClientZone;

/**
 * clientzone — Customer's subscribed services (smlouvy).
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2026 VitexSoftware
 */
require_once 'includes/Init.php';

$oPage->onlyForCustomer();

$oPage->addToHeader(new ui\PageTop(_('My services')));

$customer = \Ease\Shared::user();
$contracts = $customer->getContracts();

if (empty($contracts)) {
    $oPage->addStatusMessage(_('You have no active services yet.'), 'info');
} else {
    $listing = new \Ease\Html\TableTag(null, ['class' => 'table']);
    $listing->addRowHeaderColumns([
        'kod' => _('Code'),
        'nazev' => _('Service'),
        'smlouvaOd' => _('Valid from'),
        'typSml' => _('Type'),
    ]);

    foreach ($contracts as $contract) {
        $listing->addRowColumns([
            'kod' => \htmlspecialchars((string) ($contract['kod'] ?? ''), \ENT_QUOTES),
            'nazev' => '<strong>'.\htmlspecialchars((string) ($contract['nazev'] ?? ''), \ENT_QUOTES).'</strong>',
            'smlouvaOd' => (string) Customer::formatDate($contract['smlouvaOd'] ?? null),
            'typSml' => \htmlspecialchars(str_replace('code:', '', (string) ($contract['typSml'] ?? '')), \ENT_QUOTES),
        ]);
    }

    $oPage->container->addItem($listing);
}

$oPage->addItem(new ui\PageBottom());
$oPage->draw();
