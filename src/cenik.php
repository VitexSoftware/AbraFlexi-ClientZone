<?php

declare(strict_types=1);

namespace ClientZone;

/**
 * Former admin pricelist editor — use AbraFlexi; catalogue is index.php / pricelist.php.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2026 VitexSoftware v.s.cz
 */
require_once 'includes/Init.php';

$oPage->refusePrivilegedTool('index.php#catalog');
