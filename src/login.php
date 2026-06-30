<?php

namespace ClientZone;

/**
 * Přihlašovací stránka.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright Vitex@hippy.cz (G) 2017-2018
 */
require_once 'includes/Init.php';

if (!is_object($oUser)) {
    die(_('Please enable cookies in your browser'));
}

$oPage->addStatusMessage(_('Sign in first please'), 'warning');

$login = $oPage->getRequestValue('email');
if ($login) {
    try {
        $oUser = \Ease\Shared::user(new Customer());
        if ($oUser->tryToLogin($_POST)) {
            $oPage->redirect('index.php');
            exit;
        }
    } catch (\PDOException $exc) {
        $oPage->addStatusMessage(nl2br($exc->getTraceAsString()), 'error');
    }
    $oUser = \Ease\Shared::user(new \Ease\Anonym());
}

$oPage->addItem(new ui\PageTop(_('Sign In')));

$loginFace = new \Ease\Html\DivTag(null, ['id' => 'LoginFace']);

$oPage->container->addItem($loginFace);

$loginRow   = new \Ease\TWB5\Row();
$infoColumn = $loginRow->addItem(new \Ease\TWB5\Col(4));

$infoBlock = $infoColumn->addItem(new \Ease\TWB5\Card(new \Ease\Html\ImgTag('images/password.png')));
$infoBlock->addItem(_('Welcome to ClientZone'));

$loginColumn = $loginRow->addItem(new \Ease\TWB5\Col(4));

$submit = new \Ease\TWB5\SubmitButton(_('Sign in'), 'success');

$loginPanel = new \Ease\TWB5\Panel(new \Ease\Html\ImgTag('images/clientzone-logo.svg',
    'ClientZone', ['style' => 'width:100px']), 'success', null, $submit);
$loginPanel->addItem(new \Ease\TWB5\FormGroup(_('Username'),
    new \Ease\Html\InputTextTag('email', $login), 'name@domain.tld',
    _('usually your email address')));
$loginPanel->addItem(new \Ease\TWB5\FormGroup(_('Password'),
    new \Ease\Html\InputPasswordTag('password')));
$loginPanel->body->setTagCss(['margin' => '20px']);

$loginColumn->addItem($loginPanel);

$passRecoveryColumn = $loginRow->addItem(new \Ease\TWB5\Col(4,
    new \Ease\TWB5\LinkButton('passwordrecovery.php?email='.$login,
    '<i class="fa fa-key"></i>
'._('Lost password recovery'), 'warning')));


if($shared->getConfigValue('ALLOW_REGISTER')){

$passRecoveryColumn->additem(new \Ease\TWB5\LinkButton('newcustomer.php',
    '<i class="fa fa-user"></i>
'._('Sign On'), 'success'));

}

$oPage->container->addItem(new \Ease\TWB5\Form('Login', null, 'POST', $loginRow));

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
