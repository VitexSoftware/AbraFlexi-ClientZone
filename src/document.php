<?php

namespace ClientZone;

/**
 * clientzone - Hlavní strana.
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

if (is_null($document->getMyKey()) || !GateKeeper::isAccessibleBy($document, $oUser)) {
    $oPage->addStatusMessage(_('Wrong call'), 'error');
    $oPage->redirect('index.php');
    $oPage->draw();
    exit;
}

$oPage->addToHeader(new ui\PageTop($document->getEvidence().' '.$document));

$pdfUrl = \Ease\Functions::addUrlParams('getpdf.php', [
    'evidence' => $document->getEvidence(),
    'embed' => 'true',
    'id' => $document->getMyKey(),
]);

$oPage->container->addItem(new \Ease\Html\DivTag(
    new \Ease\Html\IframeTag($pdfUrl, [
        'class' => 'cz-pdf-frame',
        'title' => $document->getEvidence().' '.$document,
        'loading' => 'lazy',
    ]),
    ['class' => 'cz-document-view'],
));

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
