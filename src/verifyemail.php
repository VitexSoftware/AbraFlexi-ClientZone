<?php

declare(strict_types=1);

namespace ClientZone;

/**
 * clientzone - Finish registration after e-mail address verification.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2026 VitexSoftware v.s.cz
 */
require_once 'includes/Init.php';

if (\Ease\Shared::cfg('ALLOW_REGISTER') !== 'true') {
    $oPage->redirect('index.php');

    exit;
}

$data = Registration::parseToken((string) $oPage->getRequestValue('t'));

if ($data === null) {
    $oPage->addStatusMessage(_('Confirmation link is invalid or expired'), 'error');
    $oPage->redirect('newcustomer.php');

    exit;
}

if (Registration::complete($data) !== null) {
    $oPage->addStatusMessage(_('E-mail address confirmed, you can sign in now'), 'success');
    $oPage->redirect('login.php?email='.urlencode((string) $data['email']));
} else {
    $oPage->addStatusMessage(_('Registration failed'), 'error');
    $oPage->redirect('newcustomer.php');
}

exit;
