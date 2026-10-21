<?php

declare(strict_types=1);

namespace ClientZone;

/**
 * Former global AbraFlexi typeahead — disabled (could leak other companies).
 */
require_once 'includes/Init.php';

http_response_code(403);
header('Content-Type: application/json; charset=utf-8');
echo json_encode([]);
