<?php

namespace ClientZone;

/**
 * clientzone - předloha stránky.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017 VitexSoftware v.s.cz
 */
require_once './includes/Init.php';

$oPage->onlyForCustomer();

$oPage->addToHeader(new ui\PageTop(_('Spoje.Net')));

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
