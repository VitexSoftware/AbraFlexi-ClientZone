<?php

declare(strict_types=1);

namespace ClientZone;

/**
 * clientzone - Registration of a new customer login.
 *
 * Step 1: e-mail address (+ human check).
 * Step 2: known contact → username/password only; unknown e-mail → company data too.
 * See ClientZone\Registration for the rules and the REGISTER_* options.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2026 VitexSoftware v.s.cz
 */
require_once 'includes/Init.php';

if (\Ease\Shared::cfg('ALLOW_REGISTER') !== 'true') {
    $oPage->addStatusMessage(_('Sign On form is disabled by configuration'));
    $oPage->redirect('index.php');

    exit;
}

$step = 1;
$state = null;
$emailAddress = '';
$done = false;
$posted = $oPage->isPosted();

if ($posted && $oPage->getPostValue('step') === '1') {
    $emailAddress = strtolower(trim((string) $oPage->getPostValue('email_address')));
    $cube = trim((string) $oPage->getPostValue('cube'));

    if ($cube === '') {
        $oPage->addStatusMessage(_('Human control field is empty'), 'warning');
    } elseif ($cube !== '6' && strtolower($cube) !== strtolower(_('six'))) {
        $oPage->addStatusMessage(_('Are you smart human ?'), 'warning');
    } elseif (!filter_var($emailAddress, \FILTER_VALIDATE_EMAIL)) {
        $oPage->addStatusMessage(_('invalid email address'), 'warning');
    } else {
        $found = Registration::lookup($emailAddress);
        $state = $found['state'];

        if ($state === Registration::STATE_TAKEN) {
            $oPage->addStatusMessage(sprintf(_('Mail address %s is allready registered'), $emailAddress), 'warning');
        } else {
            $_SESSION['registration'] = ['email' => $emailAddress, 'human' => true];
            $step = 2;
        }
    }
} elseif ($posted && $oPage->getPostValue('step') === '2') {
    $emailAddress = strtolower(trim((string) $oPage->getPostValue('email_address')));

    if (empty($_SESSION['registration']['human']) || ($_SESSION['registration']['email'] ?? '') !== $emailAddress) {
        $oPage->addStatusMessage(_('Please start the registration again'), 'warning');
    } else {
        $step = 2;
        $found = Registration::lookup($emailAddress);
        $state = $found['state'];
        $username = Registration::usernameIsEmail() ? $emailAddress : trim((string) $oPage->getPostValue('username'));
        $password = (string) $oPage->getPostValue('password');
        $nazev = trim((string) $oPage->getPostValue('nazev'));
        $problem = null;

        if ($state === Registration::STATE_TAKEN) {
            $problem = sprintf(_('Mail address %s is allready registered'), $emailAddress);
        } elseif ($username === '') {
            $problem = _('missing login');
        } elseif (Registration::usernameTaken($username, $found['contact']['id'] ?? null)) {
            $problem = sprintf(_('Username %s is already used'), $username);
        } elseif (\strlen($password) < 8) {
            $problem = _('Password must have at least 8 characters');
        } elseif ($password !== (string) $oPage->getPostValue('password_confirm')) {
            $problem = _('Passwords do not match');
        } elseif ($state === Registration::STATE_NEW && $nazev === '') {
            $problem = _('Name is required');
        }

        if ($problem !== null) {
            $oPage->addStatusMessage($problem, 'warning');
        } else {
            $data = [
                'state' => $state,
                'email' => $emailAddress,
                'username' => $username,
                'contactId' => $found['contact']['id'] ?? null,
                'nazev' => $nazev,
                'ic' => trim((string) $oPage->getPostValue('ic')),
                'dic' => trim((string) $oPage->getPostValue('dic')),
                'tel' => trim((string) $oPage->getPostValue('tel')),
                'passwordFields' => Customer::passwordWriteFields($password),
            ];

            if (Registration::verifyEmail()) {
                // Never build the link from the Host header, it is attacker controlled.
                $baseUrl = trim((string) \Ease\Shared::cfg('CLIENTZONE_URL'));

                if ($baseUrl === '') {
                    $oPage->addStatusMessage(_('CLIENTZONE_URL is not configured, confirmation e-mail cannot be sent'), 'error');
                } elseif (!Registration::tokenSecretConfigured()) {
                    $oPage->addStatusMessage(_('REGISTRATION_SECRET is not configured, confirmation e-mail cannot be sent'), 'error');
                } elseif (Registration::sendVerification($emailAddress, Registration::makeToken($data), $baseUrl)) {
                    $oPage->addStatusMessage(sprintf(_('Confirmation link was sent to %s'), $emailAddress), 'success');
                    $done = true;
                } else {
                    $oPage->addStatusMessage(_('Confirmation e-mail could not be sent'), 'error');
                }
            } else {
                $customer = Registration::complete($data);

                if ($customer !== null) {
                    if (!filter_var(\Ease\Shared::cfg('SUPPRESS_EMAILS'), \FILTER_VALIDATE_BOOLEAN) && \Ease\Shared::cfg('SEND_INFO_TO')) {
                        $info = new \Ease\Mailer(
                            (string) \Ease\Shared::cfg('SEND_INFO_TO'),
                            sprintf(_('New clientzone account: %s'), $username),
                        );
                        $info->setMailHeaders(['From' => (string) \Ease\Shared::cfg('SEND_MAILS_FROM')]);
                        $info->addItem(_('New Customer').': '.$username."\n");
                        $info->send();
                    }

                    unset($_SESSION['registration']);

                    if (\Ease\Shared::user(new Customer())->tryToLogin(['email' => $username, 'password' => $password])) {
                        $oPage->redirect('dashboard.php');
                    } else {
                        $oPage->redirect('login.php?email='.urlencode($emailAddress));
                    }

                    exit;
                }

                $oPage->addStatusMessage(_('Registration failed'), 'error');
            }
        }
    }
}

$oPage->addToHeader(new ui\PageTop(_('Sign On')));
$oPage->addPageColumns();

$oPage->columnI->addItem(new \Ease\Html\H2Tag(_('Welcome')));
$oPage->columnI->addItem(new \Ease\Html\UlTag([
    _('After registering, you will be prompted to order service.'),
    _('All notifications target to your email'),
]));

$regFace = $oPage->columnII->addItem(new \Ease\Html\DivTag());

if ($done) {
    $regFace->addItem(new \Ease\Html\PTag(sprintf(_('Confirmation link was sent to %s'), $emailAddress)));
} else {
    $regForm = $regFace->addItem(new \Ease\TWB5\Form([
        'name' => 'create_account', 'action' => 'newcustomer.php', 'method' => 'POST', 'class' => 'form-horizontal',
    ]));
    $regForm->addItem(new \Ease\Html\InputHiddenTag('step', (string) $step));

    if ($step === 1) {
        $regForm->addInput(
            new \Ease\Html\InputTextTag('email_address', $emailAddress, ['type' => 'email']),
            _('Email Address'),
            null,
            _('Requied'),
        );
        $regForm->addInput(new \Ease\Html\InputTextTag('cube'), _('The Cube'), _('How many walls does the cube have?'), _('Only for humans'));
        $submit = _('Continue');
    } else {
        $regForm->addItem(new \Ease\Html\InputHiddenTag('email_address', $emailAddress));
        $regForm->addItem(new \Ease\Html\PTag(sprintf(_('Email Address').': %s', $emailAddress)));

        if ($state === Registration::STATE_NEW) {
            $regForm->addInput(new \Ease\Html\InputTextTag('nazev', (string) $oPage->getPostValue('nazev')), _('Name'), null, _('Requied'));
            $regForm->addInput(new \Ease\Html\InputTextTag('ic', (string) $oPage->getPostValue('ic')), _('Company ID number'), null, _('Only for company'));
            $regForm->addInput(new \Ease\Html\InputTextTag('dic', (string) $oPage->getPostValue('dic')), _('Company VAT number'), null, _('Only for company'));
            $regForm->addInput(new \Ease\Html\InputTextTag('tel', (string) $oPage->getPostValue('tel')), _('Telephone number'));
        }

        if (!Registration::usernameIsEmail()) {
            $regForm->addInput(new \Ease\Html\InputTextTag('username', (string) $oPage->getPostValue('username')), _('Username'), null, _('Requied'));
        }

        $regForm->addInput(new \Ease\Html\InputPasswordTag('password'), _('Password'), null, _('At least 8 characters'));
        $regForm->addInput(new \Ease\Html\InputPasswordTag('password_confirm'), _('Password again'));
        $submit = _('Singn On');
    }

    $regForm->addItem(new \Ease\Html\DivTag(new \Ease\Html\InputSubmitTag(
        'Register',
        $submit,
        ['title' => _('Finish'), 'class' => 'btn btn-success'],
    )));

    if ($state === Registration::STATE_TAKEN) {
        $regFace->addItem(new \Ease\Html\ATag('passwordrecovery.php', _('Lost password recovery')));
    }
}

$oPage->columnIII->addItem(new \Ease\Html\ImgTag('images/clientzone-logo.svg', \Ease\Shared::appName()));

$oPage->addItem(new ui\PageBottom());
$oPage->draw();
