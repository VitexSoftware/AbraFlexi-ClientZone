<?php

declare(strict_types=1);

/**
 * clientzone — DataTables JSON feeder for AbraFlexi document listers.
 *
 * Always scoped to the logged-in customer's company — never trust client firma.
 *
 * @author     Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2026 VitexSoftware
 */

namespace ClientZone;

require_once 'includes/Init.php';

header('Content-Type: application/json; charset=utf-8');

if (!$oUser->isLogged() || !$oUser instanceof Customer) {
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
    ReceivedInvoiceLister::class,
    ContractLister::class,
];

if ($class === null || $class === '' || !\in_array($class, $allowed, true)) {
    http_response_code(403);
    echo json_encode(['data' => [], 'error' => _('Class not allowed')], \JSON_UNESCAPED_UNICODE);

    exit;
}

if ($class === ReceivedInvoiceLister::class && !$oUser->isSupplierCustomer()) {
    http_response_code(403);
    echo json_encode(['data' => [], 'error' => _('Not a supplier-customer')], \JSON_UNESCAPED_UNICODE);

    exit;
}

if (!method_exists($class, 'getAllForDataTable')) {
    http_response_code(403);
    echo json_encode(['data' => [], 'error' => _('Invalid data source')], \JSON_UNESCAPED_UNICODE);

    exit;
}

$firma = $oUser->getAdresar()->getRecordIdent();
if (empty($firma)) {
    http_response_code(403);
    echo json_encode([
        'data' => [],
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'error' => 'No company',
    ], \JSON_UNESCAPED_UNICODE);

    exit;
}

/** @var OrderLister|InvoiceLister|ReceivedInvoiceLister $engine */
$engine = new $class();
$conditions = ['firma' => $firma];

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
