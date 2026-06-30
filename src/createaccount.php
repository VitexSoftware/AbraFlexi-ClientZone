<?php

namespace ClientZone;

/**
 * clientzone - Vytvoření účtu.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017 VitexSoftware v.s.cz
 */
require_once 'includes/Init.php';
$process = false;

$firstname = $oPage->getRequestValue('firstname');
$lastname  = $oPage->getRequestValue('lastname');

if ($oPage->isPosted()) {
    $process = true;

    $emailAddress = addslashes(strtolower($_POST['email_address']));

    if (isset($_POST['parent'])) {
        $customerParent = addslashes($_POST['parent']);
    } else {
        $customerParent = $oUser->getUserID();
    }
    $login = addslashes($_POST['login']);
    if (isset($_POST['password'])) {
        $password = addslashes($_POST['password']);
    }
    if (isset($_POST['confirmation'])) {
        $confirmation = addslashes($_POST['confirmation']);
    }

    $error = false;

    if (strlen($emailAddress) < 5) {
        $error = true;
        $oPage->addStatusMessage(_('mail address is too short'), 'warning');
    } else {
        if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
            // $error = true;
            $oPage->addStatusMessage(_('Invalid mail address'), 'warning');
        } else {
            $testuser = new \Ease\User();
            $testuser->setKeyColumn('email');
            $testuser->loadFromSQL($testuser->dblink->AddSlashes($emailAddress));
            if ($testuser->getUserName()) {
                $error = true;
                $oPage->addStatusMessage(sprintf(_('Mail addressa  %s is already registered'),
                        $emailAddress), 'warning');
            }
            unset($testuser);
        }
    }

    if (strlen($password) < 5) {
        $error = true;
        $oPage->addStatusMessage(_('Password is too short'), 'warning');
    } elseif ($password != $confirmation) {
        $error = true;
        $oPage->addStatusMessage(_('password conntrol do not match'), 'warning');
    }

    $testuser = new \Ease\User();
    $testuser->setKeyColumn('login');
    $testuser->loadFromSQL($testuser->dblink->addSlashes($login));
    $testuser->resetObjectIdentity();

    if ($testuser->getMyKey()) {
        $error = true;
        $oPage->addStatusMessage(sprintf(_('Username %s is already used. Please choose another one.'),
                $login), 'warning');
    }

    if ($error == false) {
        $newOUser = new User();
        //TODO zde by se měly doplnit defaultní hodnoty z konfiguráku registry.php
        $newOUser->setData(
            [
                'email' => $emailAddress,
//                    'parent' => (int) $customerParent,
                'login' => $login,
                $newOUser->passwordColumn => $newOUser->encryptPassword($password),
                'firstname' => $firstname,
                'lastname' => $lastname,
            ]
        );

        $userID = $newOUser->insertToSQL();

        if (!is_null($userID)) {
            $newOUser->setMyKey($userID);
            $oPage->addStatusMessage(_('User Account Created'), 'success');
            $newOUser->loginSuccess();

            $email = $oPage->addItem(new \Ease\Mailer($newOUser->getDataValue('email'),
                    _('Potvrzení registrace')));
            $email->setMailHeaders(['From' => EMAIL_FROM]);
            $email->addItem(new \Ease\Html\DivTag(_('ClientZone sign up confirmation').":\n"));
            $email->addItem(new \Ease\Html\DivTag(_('Username').': '.$newOUser->GetUserLogin()."\n"));
            $email->addItem(new \Ease\Html\DivTag(_('Password').': '.$_POST['password']."\n"));
            $email->send();

            $email = $oPage->addItem(new \Ease\Mailer(SEND_INFO_TO,
                    sprintf(_('New Registration: %s'), $newOUser->GetUserLogin())));
            $email->setMailHeaders(['From' => EMAIL_FROM]);
            $email->addItem(new \Ease\Html\DivTag(_("New Registered User")));
            $email->addItem(new \Ease\Html\DivTag(_('Username').': '.$newOUser->GetUserLogin()));
            $email->send();

            \Ease\Shared::user($newOUser)->loginSuccess();

            $oPage->redirect('index.php');
            exit;
        } else {
            $oPage->addStatusMessage(_('Cannot create new account'), 'error');
            $email = $oPage->addItem(new \Ease\Mail(constant('SEND_ORDERS_TO'),
                    'Registrace uzivatel se nezdařila'));
            $email->addItem(new \Ease\Html\DivTag('Fegistrace',
                    $oPage->PrintPre($CustomerData)));
            $email->send();
        }
    }
}

$oPage->addToHeader(new ui\PageTop(_('Sign On')));

$regFace = $oPage->container->addItem(new \Ease\TWB5\Panel(_('Sign On')));

$regForm = $regFace->addItem(new ui\ColumnsForm(new User()));
if ($oUser->getUserID()) {
    $regForm->addItem(new \Ease\Html\InputHiddenTag('parent',
            $oUser->GetUserID()));
}

$regForm->addInput(new \Ease\Html\InputTextTag('firstname', $firstname),
    _('Firstnaname'));
$regForm->addInput(new \Ease\Html\InputTextTag('lastname', $lastname),
    _('Lastname'));

$regForm->addInput(new \Ease\Html\InputTextTag('login'), _('Login').' *');
$regForm->addInput(new \Ease\Html\InputPasswordTag('password'),
    _('password').' *');
$regForm->addInput(new \Ease\Html\InputPasswordTag('confirmation'),
    _('Password Confirmation').' *');
$regForm->addInput(new \Ease\Html\InputTextTag('email_address'),
    _('email address').' *');

$regForm->addItem(new \Ease\Html\DivTag(new \Ease\Html\InputSubmitTag('Register',
            _('Register'),
            ['title' => _('Sign On'), 'class' => 'btn btn-success'])));

if (isset($_POST)) {
    $regForm->fillUp($_POST);
}

$oPage->addItem(new ui\PageBottom());
$oPage->draw();
