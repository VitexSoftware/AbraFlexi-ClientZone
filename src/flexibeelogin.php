<?php

namespace ClientZone;

/**
 * clientzone - Vytvoření účtu ve FlexiBee.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017 VitexSoftware v.s.cz
 */
require_once 'includes/Init.php';

$username = $oPage->getRequestValue('username');
$password = $oPage->getRequestValue('password');

$oPage->addItem(new ui\PageTop(_('Sign In')));

if (empty($username) || empty($password)) {
    $oPage->container->addItem(new \Ease\TWB5\Panel(_('I already have an customer number'),
        'info', new ui\FlexiBeeLoginForm()));
} else {
    $customer  = new Customer(['username' => $username, 'password' => $password]);
    $addressID = $customer->adresar->getMyKey();
    if ($addressID) {
        
    } else {

    }
}

$oPage->addItem(new ui\PageBottom());
$oPage->draw();
