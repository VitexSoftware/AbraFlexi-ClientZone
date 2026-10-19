<?php

declare(strict_types=1);

namespace ClientZone;

/**
 * clientzone - Obnova zapomenutého hesla.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2026 VitexSoftware v.s.cz
 */
require_once 'includes/Init.php';

$success = false;
$userEmail = trim((string) ($oPage->getPostValue('Email') ?: $oPage->getRequestValue('email')));

$isPost = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
if ($isPost && $userEmail !== '') {
    try {
        $customer = new Customer(['email' => $userEmail]);
        if ($customer->getUserID()) {
            $newPassword = \Ease\Functions::randomString(10);
            if ($customer->passwordChange($newPassword)) {
                $email = new \Ease\Mailer(
                    $userEmail,
                    \Ease\Shared::appName().' — '._('New password'),
                );
                $email->setMailHeaders(['From' => (string) \Ease\Shared::cfg('EMAIL_FROM')]);
                $email->addItem(_("Sign On informations was changed:\n"));
                $email->addItem(_('Username').': '.$customer->getUserLogin()."\n");
                $email->addItem(_('Password').': '.$newPassword."\n");
                $loginUrl = ($_SERVER['REQUEST_SCHEME'] ?? 'https').'://'
                    .($_SERVER['HTTP_HOST'] ?? 'localhost')
                    .dirname((string) parse_url($_SERVER['REQUEST_URI'] ?? '/login.php', \PHP_URL_PATH))
                    .'/login.php';
                $email->addItem("\n".$loginUrl."\n");
                $email->send();
                $success = true;
                $oPage->addStatusMessage(
                    sprintf(_('Your new password was sent to %s'), $userEmail),
                    'success',
                );
            } else {
                $oPage->addStatusMessage(_('Password could not be changed'), 'error');
            }
        } else {
            $oPage->addStatusMessage(
                sprintf(_('unknown email address %s'), $userEmail),
                'warning',
            );
        }
    } catch (\Throwable $exc) {
        $oPage->addStatusMessage(_('Password recovery failed. Please try again.'), 'error');
        if (\Ease\Shared::isDebug()) {
            $oPage->addStatusMessage($exc->getMessage(), 'debug');
        }
    }
} elseif ($isPost) {
    $oPage->addStatusMessage(_('Please enter your email.'), 'warning');
}

$oPage->addToHeader(new ui\PageTop(_('Lost password recovery')));

$wrap = new \Ease\Html\DivTag(null, ['class' => 'cz-auth']);
$card = $wrap->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-auth-card']));

$brand = $card->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-auth-brand']));
$brand->addItem(new \Ease\Html\ImgTag(
    'images/clientzone-logo.svg',
    \Ease\Shared::appName(),
    ['width' => 56, 'height' => 56, 'class' => 'cz-auth-logo'],
));
$brand->addItem(new \Ease\Html\DivTag(_('Account'), ['class' => 'eyebrow']));
$brand->addItem(new \Ease\Html\H1Tag(_('Lost password recovery')));

if (!$success) {
    $brand->addItem(new \Ease\Html\PTag(
        _('Forgot your password? Enter your e-mail address you entered during the registration and we will send you a new one.'),
        ['class' => 'dim'],
    ));

    $form = $card->addItem(new \Ease\TWB5\Form([
        'name' => 'PassworRecovery',
        'method' => 'POST',
        'class' => 'cz-auth-form',
        'id' => 'PassworRecovery',
    ]));
    $form->addItem(new \Ease\Html\LabelTag('Email', _('Email'), ['class' => 'form-label']));
    $form->addItem(new \Ease\Html\InputTextTag('Email', $userEmail, [
        'id' => 'Email',
        'type' => 'email',
        'class' => 'form-control form-control-lg',
        'placeholder' => 'name@domain.tld',
        'required' => 'required',
        'autocomplete' => 'email',
    ]));
    $form->addItem(new \Ease\Html\DivTag(
        new \Ease\Html\ButtonTag(_('Send New Password').' <span class="arrow">→</span>', [
            'type' => 'submit',
            'class' => 'btn btn-glow btn-lg w-100',
        ]),
        ['class' => 'cz-auth-submit'],
    ));

    $links = $card->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-auth-links']));
    $links->addItem(new \Ease\Html\ATag(
        'login.php'.($userEmail !== '' ? '?email='.urlencode($userEmail) : ''),
        _('Sign In'),
    ));
} else {
    $brand->addItem(new \Ease\Html\PTag(
        _('Please check your mailbox for new password'),
        ['class' => 'dim'],
    ));
    $card->addItem(new \Ease\Html\ATag(
        'login.php?email='.urlencode($userEmail),
        _('Sign In').' <span class="arrow">→</span>',
        ['class' => 'btn btn-glow btn-lg w-100'],
    ));
}

$oPage->container->addItem($wrap);
$oPage->addItem(new ui\PageBottom());
$oPage->draw();
