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

$contractId = filter_var(
    $oPage->getRequestValue('id'),
    \FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]],
);

$contract = new \AbraFlexi\Smlouva($contractId === false ? null : $contractId);

if ($contractId === false || is_null($contract->getMyKey()) || !GateKeeper::isAccessibleBy($contract, $oUser)) {
    $oPage->addStatusMessage(_('Wrong call'), 'error');
    $oPage->redirect('myservices.php');
    $oPage->draw();

    exit;
}

$withdrawal = new ContractWithdrawal($contract);

if ($oPage->isPosted() && $oPage->getRequestValue('action') === 'withdraw') {
    if (!CsrfToken::isValid($oPage->getRequestValue(CsrfToken::FIELD))) {
        $oPage->addStatusMessage(_('The form expired, please try again'), 'warning');
    } elseif (empty($oPage->getRequestValue('confirm'))) {
        $oPage->addStatusMessage(_('Confirm the withdrawal first'), 'warning');
    } else {
        $chosen = $_POST['item'] ?? [];

        try {
            $ends = $withdrawal->withdraw(\is_array($chosen) ? $chosen : [], $oPage->getRequestValue('scope') === 'whole');
            $oPage->addStatusMessage(
                sprintf(_('You have withdrawn from %d product(s). The last billed day is %s.'), \count($ends), (new \DateTimeImmutable(max($ends)))->format('d.m.Y')),
                'success',
            );
            // Reload the contract so the page shows the new validity
            $contract = new \AbraFlexi\Smlouva($contract->getMyKey());
            $withdrawal = new ContractWithdrawal($contract);
        } catch (\InvalidArgumentException $exc) {
            $oPage->addStatusMessage($exc->getMessage(), 'warning');
        } catch (\AbraFlexi\Exception $exc) {
            $contact = Supplier::load()->contactLine();
            $oPage->addStatusMessage(_('The withdrawal could not be saved, please contact us.').($contact !== '' ? ' '.$contact : ''), 'error');
        }
    }
}

$oPage->addToHeader(new ui\PageTop(
    _('Service').' '.\htmlspecialchars((string) $contract->getDataValue('kod'), \ENT_QUOTES),
));

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

$rows = $withdrawal->items();

if (empty($rows)) {
    $oPage->addStatusMessage(_('This contract has no products yet.'), 'info');
} else {
    $canWithdraw = false;
    $listing = new \Ease\Html\TableTag(null, ['class' => 'table']);
    $listing->addRowHeaderColumns([
        '',
        _('Code'),
        _('Product'),
        _('Quantity'),
        _('Unit price'),
        _('Total'),
        _('Valid from'),
        _('Valid to'),
    ]);

    foreach ($rows as $id => $row) {
        $active = $withdrawal->isActive($row);
        $canWithdraw = $canWithdraw || $active;
        $listing->addRowColumns([
            $active
                ? new \Ease\Html\InputTag('item[]', (string) $id, ['type' => 'checkbox', 'class' => 'form-check-input', 'aria-label' => _('Withdraw from this product')])
                : '',
            \htmlspecialchars((string) ($row['kod'] ?? ''), \ENT_QUOTES),
            \htmlspecialchars((string) ($row['nazev'] ?? ''), \ENT_QUOTES),
            \htmlspecialchars((string) ($row['mnozMj'] ?? ''), \ENT_QUOTES),
            \htmlspecialchars((string) ($row['cenaMj'] ?? ''), \ENT_QUOTES),
            \htmlspecialchars((string) ($row['cenaCelkem'] ?? ''), \ENT_QUOTES),
            ContractLister::displayValue($row['platiOdData'] ?? null),
            ContractLister::displayValue($row['platiDoData'] ?? null).($active ? '' : ' ('._('ended').')'),
        ]);
    }

    if ($canWithdraw) {
        $form = new \Ease\Html\Form(['name' => 'withdraw', 'method' => 'POST', 'action' => 'contract.php?id='.$contract->getMyKey()]);
        $form->addItem(new \Ease\Html\InputHiddenTag('action', 'withdraw'));
        $form->addItem(new \Ease\Html\InputHiddenTag(CsrfToken::FIELD, CsrfToken::get()));
        $form->addItem($listing);
        $form->addItem(new \Ease\Html\PTag(_('Withdrawing ends the billing of the chosen products. Invoices already issued stay due; the last billed day is the end of the period that has been invoiced already.'), ['class' => 'text-muted']));
        $form->addItem(new \Ease\Html\DivTag([
            new \Ease\Html\InputTag('confirm', '1', ['type' => 'checkbox', 'id' => 'withdraw-confirm', 'class' => 'form-check-input me-2']),
            new \Ease\Html\LabelTag('withdraw-confirm', _('I confirm that I withdraw from the chosen products')),
        ], ['class' => 'mb-3']));
        $form->addItem(new \Ease\Html\ButtonTag(_('Withdraw from chosen products'), ['type' => 'submit', 'name' => 'scope', 'value' => 'chosen', 'class' => 'btn btn-warning me-2']));
        $form->addItem(new \Ease\Html\ButtonTag(_('Withdraw from the whole contract'), ['type' => 'submit', 'name' => 'scope', 'value' => 'whole', 'class' => 'btn btn-danger']));
        $oPage->container->addItem($form);
    } else {
        $oPage->container->addItem($listing);
        $oPage->addStatusMessage(_('There is nothing left to withdraw from on this contract.'), 'info');
    }
}

$oPage->container->addItem(new \Ease\TWB5\LinkButton('myservices.php', '← '._('Back to my services'), 'outline-secondary', ['class' => 'mt-3']));

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
