<?php

declare(strict_types=1);

namespace ClientZone;

/**
 * clientzone — E-mail an accessible document to the logged-in customer.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2026 VitexSoftware v.s.cz
 */
require_once 'includes/Init.php';

$oPage->onlyForCustomer();

$evidence = $oPage->getRequestValue('evidence');
$documentID = $oPage->getRequestValue('id');
$back = $_SERVER['HTTP_REFERER'] ?? 'dashboard.php';

if (empty($documentID) || empty($evidence)) {
    $oPage->addStatusMessage(_('Wrong call'), 'error');
    $oPage->redirect($back);
    exit;
}

$fetcher = new \AbraFlexi\RO(
    is_numeric($documentID) ? (int) $documentID : $documentID,
    ['evidence' => $evidence],
);

if (!GateKeeper::isAccessibleBy($fetcher, $oUser)) {
    $oPage->addStatusMessage(_('Wrong call'), 'error');
    $oPage->redirect($back);
    exit;
}

$to = trim($oUser->getUserEmail());
if ($to === '' || !filter_var($to, \FILTER_VALIDATE_EMAIL)) {
    $oPage->addStatusMessage(
        _('Cannot send e-mail: your account has no valid e-mail address'),
        'warning',
    );
    $oPage->redirect($back);
    exit;
}

$kod = \AbraFlexi\Functions::uncode((string) $fetcher->getRecordCode());
$nazev = trim((string) ($fetcher->getDataValue('nazev') ?? ''));
$subject = trim($nazev.' '.$kod) !== '' ? trim($nazev.' '.$kod) : sprintf(_('Document %s'), $kod);

$documentURI = dirname(\Ease\WebPage::getUri())
    .'/getpdf.php?id='.$documentID.'&evidence='.$evidence.'&embed=true';
$body = sprintf(
    _('your document %s is in attachment'),
    (string) new \Ease\Html\ATag($documentURI, $kod),
);

try {
    $prevThrow = $fetcher->throwException;
    $fetcher->throwException = true;
    $sent = $fetcher->sendByMail($to, $subject, $body);
    $fetcher->throwException = $prevThrow;

    if ($sent) {
        $oPage->addStatusMessage(
            sprintf(_('Document was sent to %s'), $to),
            'success',
        );
    } else {
        $oPage->addStatusMessage(
            _('Mailer does not work'),
            'warning',
        );
    }
} catch (\Throwable $exc) {
    $oPage->addStatusMessage(
        sprintf(_('Sending failed: %s'), $exc->getMessage()),
        'error',
    );
}

$oPage->redirect($back);
exit;
