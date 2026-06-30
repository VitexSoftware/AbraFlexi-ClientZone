<?php

namespace ClientZone;

require_once 'includes/Init.php';

/**
 * clientzone - Objednávkový formulář
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017 VitexSoftware v.s.cz
 */
$oPage->addToHeader(new ui\PageTop(_('Spoje.Net Order Form')));

$order = new OrderItem($_REQUEST);

if ($order->checkInput()) {
    $order->addToCart();
    $order->dataReset();
}

$orderForm = new ui\OrderFormHtml($order);

$oPage->container->addItem(new \Ease\TWB5\Container(new \Ease\TWB5\Panel(_('Order'),
    'info', $orderForm)));



$oPage->addItem(new ui\PageBottom());

$oPage->draw();
