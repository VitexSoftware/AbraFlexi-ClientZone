<?php

namespace ClientZone;

/**
 * clientzone - Hlavní strana.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017 VitexSoftware v.s.cz
 */
require_once 'includes/Init.php';

$oPage->onlyForCustomer();

$id       = $oPage->getRequestValue('id');
$evidence = $oPage->getRequestValue('evidence');

$document = new \AbraFlexi\RO(is_numeric($id) ? (int) $id : $id,
    ['evidence' => $evidence]);

if (is_null($document->getMyKey()) || !GateKeeper::isAccessibleBy($document, $oUser)) {
    $oPage->addStatusMessage(_('Wrong call'), 'error');
    $oPage->redirect('index.php');
    $oPage->draw();
    exit;
}

$oPage->addToHeader(new ui\PageTop($document->getEvidence().' '.$document));

$pdfUrl = \Ease\Functions::addUrlParams('getpdf.php', [
    'evidence' => $document->getEvidence(),
    'embed' => 'true',
    'id' => $document->getMyKey(),
]);

// Where an issued invoice stands: due date, what is left to pay and, once it is overdue, the reminders sent
if ($document->getEvidence() === 'faktura-vydana') {
    // The columns are asked for explicitly: the record held by the object may be only a summary
    $rows = $document->getColumnsFromAbraFlexi(InvoiceStatus::COLUMNS, ['id' => (int) $document->getMyKey()]);
    $invoice = \is_array($rows) ? (array) (current($rows) ?: []) : [];
    $today = new \DateTimeImmutable('today');
    // A proforma invoice is paid to start with, it is not a debt that becomes overdue or is reminded
    $proforma = InvoiceStatus::isProforma($invoice, (string) \Ease\Shared::cfg('ABRAFLEXI_PROFORMA_DOCTYPE', 'ZALOHA'));
    $overdue = !$proforma && InvoiceStatus::isOverdue($invoice, $today);
    $due = Customer::formatDate($invoice['datSplat'] ?? null);
    $currency = trim(\AbraFlexi\Functions::uncode((string) ($invoice['mena'] ?? 'CZK')));
    $lines = [];

    if ($due !== null) {
        $lines[] = '<strong>'._('Due date').':</strong> '.htmlspecialchars($due);
    }

    if (InvoiceStatus::isUnpaid($invoice)) {
        $lines[] = '<strong>'._('Left to pay').':</strong> '
            .htmlspecialchars(number_format((float) $invoice['zbyvaUhradit'], 2, ',', ' ').' '.$currency);
    }

    if ($overdue) {
        $daysOverdue = InvoiceStatus::overdueDays($invoice, $today);
        $lines[] = '<strong>'.sprintf(ngettext('Overdue by %d day', 'Overdue by %d days', $daysOverdue), $daysOverdue).'</strong>';

        $reminders = InvoiceStatus::reminders($invoice);
        $lines[] = '<strong>'._('Reminders sent').':</strong> '.($reminders === []
            ? _('No reminder has been sent yet.')
            : implode(' · ', array_map(
                static fn (array $r): string => htmlspecialchars(InvoiceStatus::reminderLabel($r['level']).': '.$r['date']),
                $reminders,
            )));
    }

    if ($lines !== []) {
        $oPage->container->addItem(new \Ease\Html\DivTag(
            implode('<br>', $lines),
            ['class' => $overdue ? 'alert alert-warning cz-invoice-status' : 'alert alert-secondary cz-invoice-status'],
        ));
    }
}

$oPage->container->addItem(new \Ease\Html\DivTag(
    new \Ease\Html\IframeTag($pdfUrl, [
        'class' => 'cz-pdf-frame',
        'title' => $document->getEvidence().' '.$document,
        'loading' => 'lazy',
    ]),
    ['class' => 'cz-document-view'],
));

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
