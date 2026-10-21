<?php

declare(strict_types=1);

namespace ClientZone;

/**
 * Former DataGrid preference persistence for admin grids — disabled.
 */
require_once 'includes/Init.php';

http_response_code(403);
echo 'Forbidden';
