<?php

declare(strict_types=1);

namespace ClientZone;

/**
 * Former arbitrary-class DataGrid feeder — disabled (use abraflexidata.php).
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2026 VitexSoftware v.s.cz
 */
require_once 'includes/Init.php';

http_response_code(403);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['error' => 'Forbidden'], \JSON_UNESCAPED_UNICODE);
