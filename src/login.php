<?php

declare(strict_types=1);

namespace ClientZone;

/**
 * Přihlašovací stránka.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright Vitex@hippy.cz (G) 2017-2026
 */
require_once 'includes/Init.php';

if (!is_object($oUser)) {
    die(_('Please enable cookies in your browser'));
}

$login = trim((string) $oPage->getRequestValue('email'));
if ($login !== '') {
    $credentials = array_merge($_POST, [
        'email' => $login,
        'password' => trim((string) ($_POST['password'] ?? '')),
    ]);
    try {
        $oUser = \Ease\Shared::user(new Customer());
        if ($oUser->tryToLogin($credentials)) {
            $back = $oPage->getRequestValue('backurl') ?: 'dashboard.php';
            $oPage->redirect($back);
            exit;
        }
        $oPage->addStatusMessage(_('Invalid username or password'), 'warning');
    } catch (\AbraFlexi\Exception $exc) {
        $oPage->addStatusMessage($exc->getMessage() ?: _('Invalid username or password'), 'warning');
    } catch (\Throwable $exc) {
        $oPage->addStatusMessage(_('Sign in failed. Please try again.'), 'error');
        if (\Ease\Shared::isDebug()) {
            $oPage->addStatusMessage($exc->getMessage(), 'debug');
        }
    }
    $oUser = \Ease\Shared::user(new \Ease\Anonym());
}

$oPage->addToHeader(new ui\PageTop(_('Sign In')));

$wrap = new \Ease\Html\DivTag(null, ['class' => 'cz-auth']);
$card = $wrap->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-auth-card']));

$brand = $card->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-auth-brand']));
$brand->addItem(new \Ease\Html\ImgTag(
    'images/clientzone-logo.svg',
    \Ease\Shared::appName(),
    ['width' => 56, 'height' => 56, 'class' => 'cz-auth-logo'],
));
$brand->addItem(new \Ease\Html\DivTag(_('Sign In'), ['class' => 'eyebrow']));
$brand->addItem(new \Ease\Html\H1Tag(\Ease\Shared::appName()));
$brand->addItem(new \Ease\Html\PTag(
    _('Sign in with your account to order services and manage invoices.'),
    ['class' => 'dim'],
));

$form = $card->addItem(new \Ease\TWB5\Form([
    'name' => 'Login',
    'method' => 'POST',
    'class' => 'cz-auth-form',
]));

$backurl = $oPage->getRequestValue('backurl');
if (!empty($backurl)) {
    $form->addItem(new \Ease\Html\InputHiddenTag('backurl', $backurl));
}

$form->addItem(new \Ease\Html\LabelTag('email', _('Username'), ['class' => 'form-label']));
$form->addItem(new \Ease\Html\InputTextTag('email', $login, [
    'id' => 'email',
    'class' => 'form-control form-control-lg',
    'placeholder' => 'name@domain.tld',
    'autocomplete' => 'username',
    'required' => 'required',
]));
$form->addItem(new \Ease\Html\DivTag(
    _('Usually your email address'),
    ['class' => 'form-text'],
));

$form->addItem(new \Ease\Html\LabelTag('password', _('Password'), [
    'class' => 'form-label mt-3',
]));
$form->addItem(new \Ease\Html\InputPasswordTag('password', null, [
    'id' => 'password',
    'class' => 'form-control form-control-lg',
    'autocomplete' => 'current-password',
    'required' => 'required',
]));

$form->addItem(new \Ease\Html\DivTag(
    new \Ease\Html\ButtonTag(_('Sign in').' <span class="arrow">→</span>', [
        'type' => 'submit',
        'class' => 'btn btn-glow btn-lg w-100',
    ]),
    ['class' => 'cz-auth-submit'],
));

$links = $card->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-auth-links']));
$links->addItem(new \Ease\Html\ATag(
    'passwordrecovery.php'.($login ? '?email='.urlencode((string) $login) : ''),
    _('Lost password recovery'),
));
if ($shared->getConfigValue('ALLOW_REGISTER') == 'true') {
    $links->addItem(new \Ease\Html\ATag('newcustomer.php', _('Sign Up')));
}

$oPage->container->addItem($wrap);
$oPage->addItem(new ui\PageBottom());
$oPage->draw();
