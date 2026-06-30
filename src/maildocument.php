<?php

namespace ClientZone;

/**
 * clientzone
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017 VitexSoftware v.s.cz
 */
require_once 'includes/Init.php';

$evidence   = $oPage->getRequestValue('evidence');
$documentID = $oPage->getRequestValue('id');


if (!empty($documentID) && !empty($evidence)) {
    $fetcher = new \AbraFlexi\RO(is_numeric($documentID) ? intval($documentID)
            : $documentID, ['evidence' => $evidence]);

    $kod = \AbraFlexi\RO::uncode($fetcher->getRecordCode());

    $documentURI = dirname(\Ease\Page::getUri()).'/getpdf.php?id='.$documentID.'&evidence='.$evidence.'&embed=true';

    $fetcher->unsetDataValue('kod');
    if ($fetcher->sendByMail($oUser->getUserEmail(),
            sprintf(_('%s %s %s'), $fetcher->getDataValue('poznam'),
                $fetcher->getDataValue('nazev'), $kod),
            sprintf(_('your document %s is in attachment'),
                new \Ease\Html\ATag($documentURI, $kod)))) {
        $oPage->addStatusMessage(_('Invoice was sent'), 'success');
    } else {
        $oPage->addStatusMessage(_('Mailer does not work'), 'warning');
    }
    $oPage->redirect(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php');
} else {
    die(_('Wrong call'));
}
