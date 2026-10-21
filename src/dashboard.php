<?php

declare(strict_types=1);

namespace ClientZone;

/**
 * clientzone - Customer dashboard (post-login home).
 *
 * @author     Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2026 VitexSoftware v.s.cz
 */
require_once 'includes/Init.php';

$oPage->onlyForCustomer();

$oPage->addToHeader(new ui\PageTop(_('Dashboard')));
$oPage->container->addItem(new ui\Dashboard($oUser));
$oPage->addItem(new ui\PageBottom());
$oPage->draw();
