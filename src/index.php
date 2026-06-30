<?php

namespace ClientZone;

/**
 * clientzone - Hlavní strana.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017 VitexSoftware v.s.cz
 */
require_once 'includes/Init.php';


$oPage->addToHeader(new ui\PageTop(_('clientzone')));

$mainMenu = $oPage->container->addItem(new ui\MainPageMenu());

switch (get_class($oUser)) {
    case 'ClientZone\Customer': //Customer
        if ($shared->getConfigValue('SHOW_PRICELIST') == 'true') {
            $mainMenu->addMenuItem(
                'images/cennik.png', _('Pricelist'), 'pricelist.php'
            );
        }
        $mainMenu->addMenuItem(
            'images/order.svg', _('Order Form'), 'orderform.php'
        );

        $oPage->container->addItem(new ui\ToPayItems($oUser));

        break;
    case 'Ease\Anonym': //Anonymous
    default:
        if ($shared->getConfigValue('SHOW_PRICELIST') == 'true') {
            $mainMenu->addMenuItem(
                'images/cennik.png', _('Pricelist'), 'pricelist.php'
            );
        }
        $mainMenu->addMenuItem(
            'images/login.svg', _('Sign in'), 'login.php'
        );

        if ($shared->getConfigValue('ALLOW_REGISTER') == 'true') {
            $mainMenu->addMenuItem(
                'images/registration.svg', _('Sign up'), 'newcustomer.php'
            );
        }

        break;
}


$oPage->addItem(new ui\PageBottom());

$oPage->draw();
