<?php

declare(strict_types=1);

/**
 * clientzone - Obrázek produktu (binary response, no HTML).
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2026 VitexSoftware v.s.cz
 */

namespace ClientZone;

// Image endpoint must never emit HTML (xdebug/warnings break <img>).
ini_set('display_errors', '0');
ini_set('html_errors', '0');

require_once 'includes/Init.php';

$id = $oPage->getRequestValue('id');
if ($id === null || $id === '') {
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    echo _('Missing product ID');
    exit;
}

$type = $oPage->getRequestValue('type') ?: 'image';
$imager = new ProductImager($id);
ProductImager::output($imager->getProductImage((string) $type));
