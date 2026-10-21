<?php

declare(strict_types=1);

/**
 * clientzone — Self-service contact details (GDPR).
 *
 * Customers can review and update their own AbraFlexi contact record.
 * Only the logged-in contact is editable (no ID from the request).
 *
 * @author     Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2026 VitexSoftware
 */

namespace ClientZone;

require_once 'includes/Init.php';

$oPage->onlyForLogged();

if (!$oUser instanceof Customer) {
    $oPage->addStatusMessage(_('Please sign in with your customer account to edit contact details'), 'warning');
    $oPage->redirect('login.php?backurl='.urlencode($_SERVER['REQUEST_URI'] ?? 'mycontact.php'));
    exit;
}

$kontakt = $oUser->getKontakt();
if (empty($kontakt->getMyKey())) {
    $login = $oUser->getUserLogin();
    if ($login !== '') {
        $kontakt->loadFromAbraFlexi([$oUser->loginColumn => $login]);
    }
}
if (empty($kontakt->getMyKey())) {
    $oPage->addStatusMessage(_('Your contact record was not found'), 'error');
    $oPage->redirect('index.php');
    exit;
}

$ownId = (int) $kontakt->getMyKey();

if ($oPage->isPosted() && $oPage->getRequestValue('save_contact') === '1') {
    $payload = ['id' => $ownId];
    $errors = [];

    foreach (ui\MyContactForm::EDITABLE as $field) {
        $value = trim((string) ($oPage->getPostValue($field) ?? ''));
        $payload[$field] = $value;
    }

    if ($payload['jmeno'] === '' && $payload['prijmeni'] === '') {
        $errors[] = _('Please fill in your name');
    }
    if ($payload['email'] === '' || !filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = _('Please enter a valid email address');
    }

    if ($errors === []) {
        $oldEmail = (string) ($kontakt->getDataValue('email') ?? '');
        $oldUsername = (string) ($kontakt->getDataValue('username') ?? '');

        // Keep username in sync when it was the same as the previous email (typical login).
        if ($payload['email'] !== $oldEmail
            && ($oldUsername === '' || strcasecmp($oldUsername, $oldEmail) === 0)
        ) {
            $payload['username'] = $payload['email'];
        }

        $kontakt->insertToAbraFlexi($payload);
        if (\in_array($kontakt->lastResponseCode, [200, 201], true)) {
            $kontakt->loadFromAbraFlexi($ownId);
            $oUser->takeData($kontakt->getData() ?? []);
            if (isset($payload['username'])) {
                $oUser->userLogin = (string) $payload['username'];
            } elseif ($payload['email'] !== '') {
                $oUser->userLogin = $payload['email'];
            }
            $oUser->addStatusMessage(_('Contact details were saved'), 'success');
        } else {
            $oUser->addStatusMessage(
                _('Contact details could not be saved').' (HTTP '.$kontakt->lastResponseCode.')',
                'error',
            );
        }
    } else {
        foreach ($errors as $msg) {
            $oPage->addStatusMessage($msg, 'warning');
        }
    }
}

$displayName = trim(
    (string) ($kontakt->getDataValue('jmeno') ?? '').' '
    .(string) ($kontakt->getDataValue('prijmeni') ?? ''),
);
if ($displayName === '') {
    $displayName = $oUser->getUserLogin();
}

$oPage->addToHeader(new ui\PageTop(_('My Contact')));

$intro = new \Ease\Html\DivTag(null, ['class' => 'cz-section-intro mb-3']);
$intro->addItem(new \Ease\Html\DivTag(_('Personal data'), ['class' => 'eyebrow']));
$intro->addItem(new \Ease\Html\H2Tag(_('My contact details')));
$intro->addItem(new \Ease\Html\PTag(
    _('Under GDPR you have the right to keep your personal data accurate and up to date. '
      .'Changes are saved to your contact record in our accounting system.'),
    ['class' => 'dim'],
));
$oPage->container->addItem($intro);

$row = new \Ease\TWB5\Row();

$card = new \Ease\TWB5\Card(null, ['class' => 'cz-card']);
$card->addItem(new \Ease\Html\DivTag(
    '<strong>'.htmlspecialchars($displayName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</strong>',
    ['class' => 'card-header'],
));
$card->addItem(new \Ease\Html\DivTag(
    new ui\MyContactForm($kontakt),
    ['class' => 'card-body'],
));
$row->addColumn(8, $card);

$side = new \Ease\Html\DivTag(null, ['class' => 'cz-card p-3']);
$side->addItem(new \Ease\Html\H3Tag(_('Also available'), ['class' => 'h5']));
$side->addItem(new \Ease\Html\PTag(
    _('Company / billing address is managed separately.'),
    ['class' => 'dim small'],
));
$side->addItem(new \Ease\Html\ATag(
    'myaccount.php',
    '<i class="fa fa-building" aria-hidden="true"></i> '._('My Account'),
    ['class' => 'btn btn-line btn-sm d-block mb-2'],
));
$side->addItem(new \Ease\Html\ATag(
    'changepassword.php',
    '<i class="fa fa-lock" aria-hidden="true"></i> '._('Password change'),
    ['class' => 'btn btn-line btn-sm d-block'],
));
$row->addColumn(4, $side);

$oPage->container->addItem($row);
$oPage->addItem(new ui\PageBottom());
$oPage->draw();
