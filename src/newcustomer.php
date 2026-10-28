<?php

declare(strict_types=1);

namespace ClientZone;

/**
 * clientzone - Registration wizard for a new customer login.
 *
 * E-mail → (company details, only for an unknown e-mail) → username and password → finish.
 * A known contact, or a company having the e-mail, is never asked for details again;
 * the signed-in user can edit them on mycontact.php. See ClientZone\Registration
 * for the rules and the REGISTER_* options.
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

$session = \is_array($_SESSION['registration'] ?? null) ? $_SESSION['registration'] : [];
$step = Registration::STEP_EMAIL;
$state = $session['state'] ?? null;
$emailAddress = (string) ($session['email'] ?? '');
$posted = $oPage->isPosted();
$action = $posted ? (string) $oPage->getPostValue('step') : '';
$back = $posted && $oPage->getPostValue('back') !== null;
$sent = false;

if ($posted && $action === Registration::STEP_EMAIL) {
    $emailAddress = strtolower(trim((string) $oPage->getPostValue('email_address')));
    $cube = trim((string) $oPage->getPostValue('cube'));
    $session = [];
    $state = null;

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
        } elseif ($state === Registration::STATE_AMBIGUOUS) {
            $oPage->addStatusMessage(_('This e-mail address belongs to more companies, please contact us'), 'warning');
        } else {
            $session = ['email' => $emailAddress, 'human' => true, 'state' => $state];
            $step = Registration::steps($state)[1];
        }
    }
} elseif ($posted && \in_array($action, [Registration::STEP_COMPANY, Registration::STEP_ACCESS], true)) {
    if (empty($session['human']) || empty($session['email']) || empty($session['state'])) {
        $oPage->addStatusMessage(_('Please start the registration again'), 'warning');
        $session = [];
        $state = null;
    } else {
        $steps = Registration::steps($state);
        $step = $action;

        if ($back) {
            $step = $steps[max(0, (int) array_search($action, $steps, true) - 1)];
        } elseif ($action === Registration::STEP_COMPANY) {
            $company = [
                'nazev' => trim((string) $oPage->getPostValue('nazev')),
                'ic' => trim((string) $oPage->getPostValue('ic')),
                'dic' => trim((string) $oPage->getPostValue('dic')),
                'tel' => trim((string) $oPage->getPostValue('tel')),
            ];

            if ($company['nazev'] === '') {
                $oPage->addStatusMessage(_('Name is required'), 'warning');
            } else {
                $session['company'] = $company;
                $step = Registration::STEP_ACCESS;
            }
        } else {
            $found = Registration::lookup($emailAddress);
            $username = Registration::usernameIsEmail() ? $emailAddress : trim((string) $oPage->getPostValue('username'));
            $password = (string) $oPage->getPostValue('password');
            $problem = null;

            if ($found['state'] !== $state) {
                $problem = _('Please start the registration again');
                $session = [];
                $state = null;
                $step = Registration::STEP_EMAIL;
            } elseif ($username === '') {
                $problem = _('missing login');
            } elseif (Registration::usernameTaken($username, $found['contact']['id'] ?? null)) {
                $problem = sprintf(_('Username %s is already used'), $username);
            } elseif (\strlen($password) < 8) {
                $problem = _('Password must have at least 8 characters');
            } elseif ($password !== (string) $oPage->getPostValue('password_confirm')) {
                $problem = _('Passwords do not match');
            }

            if ($problem !== null) {
                $oPage->addStatusMessage($problem, 'warning');
            } else {
                $data = [
                    'state' => $state,
                    'email' => $emailAddress,
                    'username' => $username,
                    'contactId' => $found['contact']['id'] ?? null,
                    'companyId' => $found['company']['id'] ?? null,
                    'passwordFields' => Customer::passwordWriteFields($password),
                ] + ($session['company'] ?? []);

                if (Registration::mustVerify($state)) {
                    // Never build the link from the Host header, it is attacker controlled.
                    $baseUrl = trim((string) \Ease\Shared::cfg('CLIENTZONE_URL'));

                    if ($baseUrl === '') {
                        $oPage->addStatusMessage(_('CLIENTZONE_URL is not configured, confirmation e-mail cannot be sent'), 'error');
                    } elseif (!Registration::tokenSecretConfigured()) {
                        $oPage->addStatusMessage(_('REGISTRATION_SECRET is not configured, confirmation e-mail cannot be sent'), 'error');
                    } elseif (Registration::sendVerification($emailAddress, Registration::makeToken($data), $baseUrl)) {
                        $oPage->addStatusMessage(sprintf(_('Confirmation link was sent to %s'), $emailAddress), 'success');
                        $step = Registration::STEP_DONE;
                        $sent = true;
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
                            if ($state !== Registration::STATE_NEW) {
                                $oPage->addStatusMessage(_('Please check your contact details'), 'info');
                            }

                            $oPage->redirect($state === Registration::STATE_NEW ? 'dashboard.php' : 'mycontact.php');
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
}

if ($step === Registration::STEP_DONE) {
    unset($_SESSION['registration']);
} elseif ($session !== []) {
    $_SESSION['registration'] = $session;
} else {
    unset($_SESSION['registration']);
}

$oPage->addToHeader(new ui\PageTop(_('Sign On')));

$wrap = new \Ease\Html\DivTag(null, ['class' => 'cz-auth']);
$card = $wrap->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-auth-card cz-auth-wide']));

$brand = $card->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-auth-brand']));
$brand->addItem(new \Ease\Html\ImgTag(
    'images/clientzone-logo.svg',
    \Ease\Shared::appName(),
    ['width' => 56, 'height' => 56, 'class' => 'cz-auth-logo'],
));
$brand->addItem(new \Ease\Html\DivTag(_('Account'), ['class' => 'eyebrow']));
$brand->addItem(new \Ease\Html\H1Tag(_('Sign On')));

$card->addItem(new ui\WizardSteps(Registration::steps($state), $step));

if ($step === Registration::STEP_DONE) {
    $card->addItem(new \Ease\Html\PTag(
        $sent ? sprintf(_('Confirmation link was sent to %s'), $emailAddress) : _('Registration failed'),
        ['class' => 'dim'],
    ));
    $card->addItem(new \Ease\Html\ATag('login.php', _('Sign In').' <span class="arrow">→</span>', ['class' => 'btn btn-glow btn-lg w-100']));
} else {
    $form = $card->addItem(new \Ease\TWB5\Form([
        'name' => 'create_account', 'action' => 'newcustomer.php', 'method' => 'POST', 'class' => 'cz-auth-form',
    ]));
    $form->addItem(new \Ease\Html\InputHiddenTag('step', $step));
    $buttons = new \Ease\Html\DivTag(null, ['class' => 'cz-auth-submit d-flex gap-2']);

    if ($step !== Registration::STEP_EMAIL) {
        $buttons->addItem(new \Ease\Html\ButtonTag(_('Back'), [
            'type' => 'submit', 'name' => 'back', 'value' => '1', 'formnovalidate' => 'formnovalidate', 'class' => 'btn btn-line btn-lg',
        ]));
    }

    if ($step === Registration::STEP_EMAIL) {
        $form->addItem(new \Ease\Html\PTag(_('Start with your e-mail address. We will check whether we already know you.'), ['class' => 'dim']));
        $form->addInput(
            new \Ease\Html\InputTextTag('email_address', $emailAddress, ['type' => 'email', 'required' => 'required', 'autocomplete' => 'email']),
            _('Email Address'),
        );
        $form->addInput(new \Ease\Html\InputTextTag('cube', '', ['required' => 'required']), _('The Cube'), _('How many walls does the cube have?'), _('Only for humans'));
        $label = _('Continue');
    } elseif ($step === Registration::STEP_COMPANY) {
        $form->addItem(new \Ease\Html\PTag(_('We do not know this e-mail yet. Tell us about your company.'), ['class' => 'dim']));
        $company = $session['company'] ?? [];
        $form->addInput(new \Ease\Html\InputTextTag('nazev', (string) ($company['nazev'] ?? ''), ['required' => 'required']), _('Name'), null, _('Requied'));
        $form->addInput(new \Ease\Html\InputTextTag('ic', (string) ($company['ic'] ?? '')), _('Company ID number'), null, _('Only for company'));
        $form->addInput(new \Ease\Html\InputTextTag('dic', (string) ($company['dic'] ?? '')), _('Company VAT number'), null, _('Only for company'));
        $form->addInput(new \Ease\Html\InputTextTag('tel', (string) ($company['tel'] ?? ''), ['type' => 'tel']), _('Telephone number'));
        $label = _('Continue');
    } else {
        $form->addItem(new \Ease\Html\PTag(
            $state === Registration::STATE_NEW
                ? sprintf(_('Choose how you will sign in as %s.'), htmlspecialchars($emailAddress))
                : sprintf(_('We already know %s, only the sign-in details are missing.'), htmlspecialchars($emailAddress)),
            ['class' => 'dim'],
        ));

        if (Registration::mustVerify($state)) {
            $form->addItem(new \Ease\Html\PTag(_('We will send a confirmation link to this address.'), ['class' => 'dim']));
        }

        if (!Registration::usernameIsEmail()) {
            $form->addInput(new \Ease\Html\InputTextTag('username', (string) $oPage->getPostValue('username'), ['required' => 'required', 'autocomplete' => 'username']), _('Username'), null, _('Requied'));
        }

        $form->addInput(new \Ease\Html\InputPasswordTag('password', '', ['required' => 'required', 'minlength' => '8', 'autocomplete' => 'new-password']), _('Password'), null, _('At least 8 characters'));
        $form->addInput(new \Ease\Html\InputPasswordTag('password_confirm', '', ['required' => 'required', 'minlength' => '8', 'autocomplete' => 'new-password']), _('Password again'));
        $label = Registration::mustVerify($state) ? _('Send confirmation link') : _('Singn On');
    }

    $buttons->addItem(new \Ease\Html\ButtonTag($label.' <span class="arrow">→</span>', ['type' => 'submit', 'class' => 'btn btn-glow btn-lg flex-grow-1']));
    $form->addItem($buttons);
}

$links = $card->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-auth-links']));
$links->addItem(new \Ease\Html\ATag('login.php', _('Sign In')));

if ($state === Registration::STATE_TAKEN) {
    $links->addItem(new \Ease\Html\ATag('passwordrecovery.php', _('Lost password recovery')));
}

$oPage->container->addItem($wrap);
$oPage->addItem(new ui\PageBottom());
$oPage->draw();
