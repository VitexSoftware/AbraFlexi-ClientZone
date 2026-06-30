<?php

namespace ClientZone;

require_once 'includes/Init.php';

$oPage->onlyForLogged('login.php',
    _('Please sign in to finish your order'));

/**
 * clientzone - Hlavní strana.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017 VitexSoftware v.s.cz
 */
$oPage->addItem(new ui\PageTop(_('Spoje.Net Order Form')));


$order    = new Order(isset($_SESSION['cart']) ? $_SESSION['cart'] : []);
$finished = $order->finishOrder();

if (count($finished)) {


    $infobox = new \Ease\TWB5\Panel(_('Order Accepted'), 'success');

    foreach ($finished as $did => $document) {

        if ($document->lastResponseCode == 201) {
            $_SESSION['cart'] = [];
            $order->addStatusMessage(sprintf(_('Order %s was accepted'),
                    $document), 'success');

            $type     = $document->getDataValue('poznam');
            $emails[] = $order->customer->adresar->getDataValue('email');
            $emails[] = $order->customer->kontakt->getDataValue('email');
            $email    = implode(',', array_unique($emails));
            if (strlen($email)) {
                if ($document->sendByMail($email, $type.' '.$document,
                        sprintf(_('%s for your order'),
                            $document->getDataValue('poznam')))) {
                    $document->addStatusMessage(sprintf(_('Proforma was sent to %s'),
                            $email), 'mail');
                }
            }
            $document->addStatusMessage(sprintf(_('Get Your %s Here %s'), $type,
                    '<a href="getpdf.php?type='.$document->getEvidence().'&id='.$did.'">'.str_replace('code:',
                        '', $document).'</a>'), 'success');

            $infobox->addItem(
                new \Ease\TWB5\Panel($type.' '.str_replace('code:', '', $document),
                'info',
                new ui\EmbedResponsivePDF('getpdf.php?evidence='.$document->getEvidence().'&id='.$document->getMyKey().'&embed=true'),
                new ui\DownloadInvoiceButton($document))
            );
        } else {
            
        }
    }
    if (!$infobox->isEmpty()) {
        $oPage->container->addItem(new \Ease\TWB5\Container($infobox));
    }
} else {
    $order->addStatusMessage(_('The order is empty'), 'warning');
}
$oPage->addItem(new ui\PageBottom());

$oPage->draw();
