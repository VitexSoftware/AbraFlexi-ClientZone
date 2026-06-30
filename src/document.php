<?php

namespace ClientZone;

/**
 * clientzone - Hlavní strana.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017 VitexSoftware v.s.cz
 */
require_once 'includes/Init.php';

$oPage->onlyForLogged();

$id       = $oPage->getRequestValue('id');
$evidence = $oPage->getRequestValue('evidence');

$document = new \AbraFlexi\RO(is_numeric($id) ? intval($id) : $id,
    ['evidence' => $evidence]);

$oPage->addItem(new ui\PageTop($document->getEvidence().' '.$document));

$embed = new \AbraFlexi\ui\TWB5\EmbedResponsivePDF($document);
$embed->setTagCss(['min-height'=>'100vh','width'=>'100%']);

$oPage->container->addItem(new \Ease\Html\DivTag($embed,
        ['style' => 'width: 100%; height: 100%']));

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
