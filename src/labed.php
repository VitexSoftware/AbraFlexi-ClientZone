<?php

declare(strict_types=1);

namespace ClientZone;

/**
 * Former label editor AJAX — disabled.
 */
require_once 'includes/Init.php';

http_response_code(403);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['error' => 'Forbidden'], \JSON_UNESCAPED_UNICODE);
