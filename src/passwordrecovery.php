<?php

namespace ClientZone;

/**
 * clientzone - User Class.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017 VitexSoftware v.s.cz
 */
require_once 'includes/Init.php';

$success = false;

$userEmail = $oPage->getPostValue('Email');

\Ease\Part::jQueryze();
$oPage->includeJavaScript('js/jquery.validate.js');
$oPage->addJavascript('$("#PassworRecovery").validate({
  rules: {
    Email: {
      required: true,
      email: true
    }
  }
});', null, true);

if ($userEmail) {
    $customer = new Customer();
    $customer->kontakt->loadFromAbraFlexi(['email' => $userEmail]);
    if ($customer->kontakt->getMyKey()) {
        $newPassword = \Ease\Sand::randomString(8);
        if ($customer->passwordChange($newPassword)) {
            $customer->addStatusMessage(_('Password was changed'));


            $email = $oPage->addItem(new \Ease\Mailer($userEmail,
                _('clientzone').' - '.sprintf(_('New password for %s'),
                    $_SERVER['SERVER_NAME'])));
            $email->setMailHeaders(['From' => constant('EMAIL_FROM')]);
            $email->addItem(_("Sign On informations was changed:\n"));

            $email->addItem(_('Username').': '.$customer->getUserLogin()."\n");
            $email->addItem(_('Password').': '.$newPassword."\n");

            $loginUrl = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].dirname(\Ease\WebPage::getUri()).'/login.php';
            $email->addItem("\n$loginUrl\n");

            $email->send();

//            $oPage->addStatusMessage(sprintf(_('Your new password was sent to %s'),
//                    '<strong>'.$userEmail.'</strong>'));
            $success = true;
        }
    } else {
        $oPage->addStatusMessage(sprintf(_('unknown email address %s'),
                '<strong>'.$userEmail.'</strong>'), 'warning');
    }
} else {
    $oPage->addStatusMessage(_('Please enter your email.'));
}

$oPage->addToHeader(new ui\PageTop(_('Lost password recovery')));

$row       = $oPage->container->addItem(new \Ease\TWB5\Row());
$columnI   = $row->addItem(new \Ease\TWB5\Col(4));
$columnII  = $row->addItem(new \Ease\TWB5\Col(4));
$columnIII = $row->addItem(new \Ease\TWB5\Col(4));

if (!$success) {
    $columnI->addItem(new \Ease\Html\H1Tag('Lost password'));

    $columnII->addItem(_('Forgot your password? Enter your e-mail address you entered during the registration and we will send you a new one.'));

    $emailForm = $columnII->addItem(new \Ease\TWB5\Form(['name' => 'PassworRecovery', 'method' => 'POST']));


    $emailForm->addInput(new \Ease\Html\InputTextTag('Email',
        $oPage->getRequestValue('email'), ['type' => 'email']), _('Email'));
    $emailForm->addItem(new \Ease\TWB5\SubmitButton(_('Send New Password'),
        'warning'));

    if (isset($_POST)) {
        $emailForm->fillUp($_POST);
    }
} else {
    $columnII->addItem(new \Ease\TWB5\Card([_('Please check your mailbox for new password')
        , ' '._('and').' ', new \Ease\TWB5\LinkButton('login.php?email='.$userEmail,
            _('Sign In'), 'success')]));
}

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
