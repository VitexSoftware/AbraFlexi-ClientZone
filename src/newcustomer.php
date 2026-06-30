<?php

namespace ClientZone;

/**
 * clientzone - Vytvoření účtu ve FlexiBee.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2019 VitexSoftware v.s.cz
 */
require_once 'includes/Init.php';

if ($shared->getConfigValue('ALLOW_REGISTER') != 'true') {
    $oPage->addStatusMessage('Sing On form is disabled by configuration');
    $oPage->redirect('index.php');
    exit();
}

$process = false;

$nazev         = $oPage->getRequestValue('nazev');
$email_address = $oPage->getPostValue('email_address');
$ico           = $oPage->getRequestValue('ic');
$dic           = $oPage->getRequestValue('dic');
$tel           = $oPage->getRequestValue('tel');
$cube          = $oPage->getRequestValue('cube');


if ($oPage->isPosted()) {
    $process = true;
    $error   = false;

    $emailAddress = addslashes(strtolower($_POST['email_address']));

    if (empty($cube)) {
        $oPage->addStatusMessage(_('Human control field is empty'), 'warning');
        $error = true;
    } else {

        if (($cube == '6') || ($cube == _('six'))) {
            if (strlen($emailAddress) < 5) {
                $error = true;
                $oPage->addStatusMessage(_('Mail address is too short'),
                    'warning');
            } else {
                if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
                    $error = true;
                    $oPage->addStatusMessage(_('invalid email address'),
                        'warning');
                } else {
                    $tester = new Customer(['email' => $emailAddress]);

                    if (!is_null($tester->adresar->getMyKey()) || !is_null($tester->kontakt->getMyKey())) {
                        $error = true;
                        $oPage->addStatusMessage(sprintf(_('Mail address %s is allready registered'),
                                $emailAddress), 'warning');
                    }
                }
            }
        } else {
            $oPage->addStatusMessage(_('Are you smart human ?'), 'warning');
            $error = true;
        }
    }

    if ($error === false) {

        $newOUser = new Customer();
        $newOUser->adresar->setData(
            [
                'email' => $emailAddress,
                'nazev' => $nazev,
                'ic' => $ico,
                'dic' => $dic
            ]
        );

        if ($newOUser->adresar->sync()) {
            $companyID = $newOUser->adresar->getRecordID();
            $newOUser->addStatusMessage(_('Company registered'), 'success');

            $password = \Ease\Sand::randomString(8);

            $newOUser->kontakt->setData([
                'email' => $emailAddress,
                'username' => $emailAddress,
                'password' => $newOUser->encryptPassword($password),
                'firma' => $newOUser->adresar
                ]
            );


            if ($newOUser->kontakt->sync()) {
                $newOUser->addStatusMessage(_('User account created'), 'success');
                $contactID = $newOUser->kontakt->getRecordID();

                $email = $oPage->addItem(new \Ease\Mailer($newOUser->getDataValue('email'),
                    _('Sign On info')));
                $email->setMailHeaders(['From' => constant('SEND_MAILS_FROM')]);
                $email->addItem(new \Ease\Html\H1Tag(_('clientzone Account')));
                $email->addItem(new \Ease\Html\DivTag(' Login: '.$emailAddress."\n"));
                $email->addItem(new \Ease\Html\DivTag(' Password: '.$password."\n"));
                $email->send();

                $email = $oPage->addItem(new \Ease\Mailer(constant('SEND_INFO_TO'),
                    sprintf(_('New clientzone account: %s'),
                        $newOUser->getUserLogin())));
                $email->setMailHeaders(['From' => constant('SEND_MAILS_FROM')]);
                $email->addItem(new \Ease\Html\DivTag(_("New Customer").':\n'));
                $email->addItem(new \Ease\Html\DivTag(
                    ' Login: '.$emailAddress."\n", ['id' => 'login']));
                $email->addItem(new \Ease\Html\ATag($newOUser->adresar->getApiURL(),
                    $newOUser->adresar));
                $email->send();

                \Ease\Shared::user($newOUser)->loginSuccess();

                $oPage->redirect('index.php');
                exit;
            } else {
                $oUser->addStatusMessage(_('Error saving Contact'), 'error');
                $email = $oPage->addItem(new Ease\Mailer(constant('SEND_ORDERS_TO'),
                    'Sign on Failed'));
                $email->addItem(serialize($newOUser->kontakt->getData()));
                $email->send();
            }
        } else {
            $oUser->addStatusMessage(_('Error saving Company'), 'error');
            $email = $oPage->addItem(new \Ease\Mailer(constant('SEND_ORDERS_TO'),
                'Sign on Failed'));
            $email->addItem(serialize($newOUser->adresar->getData()));
            $email->send();
        }
    } else {
        $oUser = \Ease\Shared::user(new \Ease\Anonym());
    }
}

$oPage->addItem(new ui\PageTop(_('Sign On')));
$oPage->addPageColumns();

$oPage->columnI->addItem(new \Ease\Html\H2Tag(_('Welcome')));
$oPage->columnI->addItem(
    new \Ease\Html\UlTag(
    [
    _('After registering, you will be prompted to order service.'),
    _('All notifications target to your email'),
    ]
    )
);

$regFace = $oPage->columnII->addItem(new \Ease\Html\DivTag());

$regForm = $regFace->addItem(new \Ease\TWB5\Form('create_account',
    'newcustomer.php', 'POST', null, ['class' => 'form-horizontal']));
if ($oUser->getUserID()) {
    $regForm->addItem(new \Ease\Html\InputHiddenTag('u_parent',
        $oUser->getUserID()));
}


$regForm->addInput(new \Ease\Html\InputTextTag('nazev', $nazev), _('Name'),
    null, _('Requied'));

$regForm->addInput(
    new \Ease\Html\InputTextTag('email_address', $email_address,
    ['type' => 'email']), _('Email Address'), null, _('Requied'));
$regForm->addInput(
    new \Ease\Html\InputTextTag('tel', $tel), _('Telephone number'));

$regForm->addInput(new \Ease\Html\InputTextTag('ic', $ico),
    _('Company ID number'), null, _('Only for company'));
$regForm->addInput(new \Ease\Html\InputTextTag('dic', $dic),
    _('Company VAT number'), null, _('Only for company'));

$regForm->addInput(new \Ease\Html\InputTextTag('cube'), _('The Cube'),
    _('How many walls does the cube have?'), _('Only for humans'));

//$regForm->addInput(new \Ease\Html\CheckboxTag('consent'), _('GDPR Consent'),
//    false, _('Agree with').' <a href=eula.php>'._('Agreement').'</a>');


$regForm->addItem(new \Ease\Html\DivTag(
    new \Ease\Html\InputSubmitTag('Register', _('Singn On'),
    ['title' => _('Finish'), 'class' => 'btn btn-success'])));

$oPage->columnIII->addItem(new \Ease\Html\ImgTag('images/clientzone-logo.svg',
    constant('EASE_APPNAME')));

if (isset($_POST)) {
    $regForm->fillUp($_POST);
}

$oPage->addItem(new ui\PageBottom());
$oPage->draw();
