<?php

declare(strict_types=1);

namespace ClientZone;

/**
 * clientzone - Změna hesla klienta.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2026 VitexSoftware
 */
require_once 'includes/Init.php';

$oPage->onlyForCustomer();

$formOK = true;

if ($oPage->isPosted()) {
    $password = (string) $oPage->getPostValue('password');
    $passwordConfirm = (string) $oPage->getPostValue('passwordConfirm');
    $currentPassword = (string) $oPage->getPostValue('CurrentPassword');

    if ($password === '') {
        $oUser->addStatusMessage(_('Please enter new password'), 'warning');
        $formOK = false;
    } elseif ($password === $oUser->getUserLogin()) {
        $oUser->addStatusMessage(_('Password cant match with login'), 'warning');
        $formOK = false;
    }

    if ($passwordConfirm === '') {
        $oPage->addStatusMessage(_('Please enter password confirmation'), 'warning');
        $formOK = false;
    } elseif ($passwordConfirm !== $password) {
        $oUser->addStatusMessage(_('Password control do not match'), 'warning');
        $formOK = false;
    }

    if ($currentPassword === '') {
        $oPage->addStatusMessage(_('Please enter current password'), 'warning');
        $formOK = false;
    } elseif (!$oUser->passwordCheck($currentPassword)) {
        $oPage->addStatusMessage(_('Current password invalid'), 'warning');
        $formOK = false;
    }
}

$oPage->addToHeader(new ui\PageTop(_('User password change')));

if ($formOK && $oPage->isPosted()) {
    $plainPass = (string) $oPage->getRequestValue('password');

    if ($oUser->passwordChange($plainPass)) {
        $oUser->addStatusMessage(_('Password was changed'), 'success');

        $mailTo = (string) $oUser->getDataValue($oUser->mailColumn);
        if ($mailTo !== '') {
            $email = new \Ease\Mailer(
                $mailTo,
                \Ease\Shared::appName().' '._('Changed password'),
            );
            $email->addItem(sprintf(
                _('Dear user %s, your password was changed to'),
                $oUser->getUserLogin(),
            ).":\n");
            $email->addItem(_('Password').': '.$plainPass."\n");

            $loginUrl = ($_SERVER['REQUEST_SCHEME'] ?? 'https').'://'
                .($_SERVER['HTTP_HOST'] ?? 'localhost')
                .dirname(\Ease\WebPage::getUri()).'/login.php';
            $email->addItem("\n$loginUrl\n");
            $email->send();
        }
    }
} else {
    $form = new \Ease\Html\Form([
        'name' => 'ChangePassword',
        'method' => 'POST',
        'class' => 'cz-auth-form',
    ]);

    $form->addItem(new \Ease\TWB5\FormGroup(
        _('Current password'),
        new \Ease\Html\InputPasswordTag('CurrentPassword', null, [
            'class' => 'form-control',
            'autocomplete' => 'current-password',
            'required' => 'required',
        ]),
    ));
    $form->addItem(new \Ease\TWB5\FormGroup(
        _('New Password'),
        new \Ease\Html\InputPasswordTag('password', null, [
            'class' => 'form-control',
            'autocomplete' => 'new-password',
            'required' => 'required',
        ]),
    ));
    $form->addItem(new \Ease\TWB5\FormGroup(
        _('Password confirm'),
        new \Ease\Html\InputPasswordTag('passwordConfirm', null, [
            'class' => 'form-control',
            'autocomplete' => 'new-password',
            'required' => 'required',
        ]),
    ));

    $actions = new \Ease\Html\DivTag(null, ['class' => 'cz-form-actions d-flex flex-wrap gap-2 mt-3']);
    $actions->addItem(new \Ease\TWB5\SubmitButton(_('Change password'), 'primary'));
    $actions->addItem(new \Ease\Html\ATag(
        'mycontact.php',
        _('Back to my contact'),
        ['class' => 'btn btn-line'],
    ));
    $form->addItem($actions);

    $oPage->container->addItem(new \Ease\TWB5\Panel(
        _('Password change'),
        'default',
        $form,
    ));
}

$oPage->addItem(new ui\PageBottom());
$oPage->draw();
