<?php

declare(strict_types=1);

namespace ClientZone;

/**
 * Former free-form contact editor — use mycontact.php (own contact only).
 */
require_once 'includes/Init.php';

$oPage->refusePrivilegedTool('mycontact.php');
