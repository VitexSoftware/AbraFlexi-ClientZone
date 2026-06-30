<?php

namespace ClientZone;

/**
 * ClientZone - Customer's settings page.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2019 VitexSoftware v.s.cz
 */
require_once 'includes/Init.php';

$oPage->onlyForUser();

$oPage->addToHeader(new ui\PageTop(_('Settings')));

$oPage->container->addItem(new \Ease\TWB5\LinkButton('changepassword.php',
    _('Change Password'), 'warning'));

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
