<?php

namespace ClientZone;

/**
 * clientzone
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017 VitexSoftware v.s.cz
 */
require_once 'includes/Init.php';

$oPage->onlyForCustomer();

$embed    = $oPage->getRequestValue('embed');
$id       = $oPage->getRequestValue('id');
$evidence = $oPage->getRequestValue('evidence');

$document = new \AbraFlexi\RO(is_numeric($id) ? (int) $id : $id,
    ['evidence' => $evidence]);

if (!is_null($document->getMyKey()) && GateKeeper::isAccessibleBy($document,
        $oUser)) {
    $documentBody = $document->getInFormat('pdf');

    $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_',
        $document->getEvidence().'_'.$document).'.pdf';
    $disposition = ($embed == 'true') ? 'inline' : 'attachment';

    header('Content-Type: application/pdf');
    header('Content-Disposition: '.$disposition.'; filename="'.$safeName.'"');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-cache');
    header('Content-Length: '.strlen($documentBody));
    echo $documentBody;
} else {
    die(_('Wrong call'));
}
