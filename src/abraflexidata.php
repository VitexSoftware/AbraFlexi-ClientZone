<?php

declare(strict_types=1);

/**
 * clientzone — DataTables JSON feeder for AbraFlexi document listers.
 *
 * Pattern mirrors MultiFlexi ajax2db.php: whitelist + getAllForDataTable().
 *
 * @author     Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2026 VitexSoftware
 */

namespace ClientZone;

require_once 'includes/Init.php';

header('Content-Type: application/json; charset=utf-8');

if (!$oUser->isLogged()) {
    echo json_encode([
        'data' => [],
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'error' => 'Unauthenticated',
        'session_expired' => true,
        'redirect' => 'login.php',
    ], \JSON_UNESCAPED_UNICODE);

    exit;
}

$class = $oPage->getRequestValue('class');

$allowed = [
    OrderLister::class,
    InvoiceLister::class,
];

if ($class === null || $class === '' || !\in_array($class, $allowed, true)) {
    http_response_code(403);
    echo json_encode(['data' => [], 'error' => 'Class not allowed'], \JSON_UNESCAPED_UNICODE);

    exit;
}

if (!method_exists($class, 'getAllForDataTable')) {
    http_response_code(403);
    echo json_encode(['data' => [], 'error' => 'Invalid data source'], \JSON_UNESCAPED_UNICODE);

    exit;
}

/** @var OrderLister|InvoiceLister $engine */
$engine = new $class();

// Always scope to the logged-in customer's company — never trust client firma.
$conditions = [];
if ($oUser instanceof Customer) {
    $firma = $oUser->getAdresar()->getRecordIdent();
    if (!empty($firma)) {
        $conditions['firma'] = $firma;
    }
}

try {
    echo json_encode($engine->getAllForDataTable($conditions), \JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    http_response_code(200);
    echo json_encode([
        'data' => [],
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'error' => $e->getMessage(),
    ], \JSON_UNESCAPED_UNICODE);
    error_log('abraflexidata ('.$class.'): '.$e->getMessage());
}
