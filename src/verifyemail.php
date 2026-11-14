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

$token = (string) $oPage->getRequestValue('t');
$data = Registration::parseToken($token);

if ($data === null) {
    $oPage->addStatusMessage(_('Confirmation link is invalid or expired'), 'error');
    $oPage->redirect('newcustomer.php');

    exit;
}

// E-mail security scanners open every link of a message (GET/HEAD). Opening the link must not
// finish the registration, only the customer's own confirmation (POST) does.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    $oPage->addToHeader(new ui\PageTop(_('Confirm e-mail address')));

    $wrap = new \Ease\Html\DivTag(null, ['class' => 'cz-auth']);
    $card = $wrap->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-auth-card']));
    $brand = $card->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-auth-brand']));
    $brand->addItem(new \Ease\Html\ImgTag(
        'images/clientzone-logo.svg',
        \Ease\Shared::appName(),
        ['width' => 56, 'height' => 56, 'class' => 'cz-auth-logo'],
    ));
    $brand->addItem(new \Ease\Html\DivTag(_('Account'), ['class' => 'eyebrow']));
    $brand->addItem(new \Ease\Html\H1Tag(_('Confirm e-mail address')));
    $card->addItem(new \Ease\Html\PTag(
        sprintf(_('Confirm that %s is your address and finish the registration.'), htmlspecialchars((string) $data['email'])),
        ['class' => 'dim'],
    ));
    $form = $card->addItem(new \Ease\TWB5\Form(['name' => 'verify_email', 'action' => 'verifyemail.php', 'method' => 'POST', 'class' => 'cz-auth-form']));
    $form->addItem(new \Ease\Html\InputHiddenTag('t', $token));
    $form->addItem(new \Ease\Html\ButtonTag(_('Confirm e-mail address').' <span class="arrow">→</span>', ['type' => 'submit', 'class' => 'btn btn-glow btn-lg w-100']));

    $oPage->container->addItem($wrap);
    $oPage->addItem(new ui\PageBottom());
    $oPage->draw();

    exit;
}

if (Registration::complete($data) !== null || Registration::alreadyConfirmed($data)) {
    $oPage->addStatusMessage(_('E-mail address confirmed, you can sign in now'), 'success');
    $oPage->redirect('login.php?email='.urlencode((string) $data['email']));
} else {
    $oPage->addStatusMessage(_('Registration failed'), 'error');
    $oPage->redirect('newcustomer.php');
}

exit;
