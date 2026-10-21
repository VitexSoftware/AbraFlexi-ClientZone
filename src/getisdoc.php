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

$id       = $oPage->getRequestValue('id');
$evidence = $oPage->getRequestValue('evidence');

$document = new \AbraFlexi\RO(is_numeric($id) ? (int) $id : $id,
    ['evidence' => $evidence]);

if (!is_null($document->getMyKey()) && GateKeeper::isAccessibleBy($document,
        $oUser)) {
    $documentBody = $document->getInFormat('isdoc');

    header('Content-Description: File Transfer');
    header('Content-Disposition: attachment; filename='.$document->getEvidence().'_'.$document.'.isdoc');
    header('Content-Type: application/octet-stream');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: '.strlen($documentBody));
    echo $documentBody;
} else {
    die(_('Wrong call'));
}
