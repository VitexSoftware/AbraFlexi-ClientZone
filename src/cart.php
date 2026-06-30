<?php
/**
 * clientzone - Přehled Košíku.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017 VitexSoftware v.s.cz
 */

namespace ClientZone;

require_once 'includes/Init.php';

if (!isset($_SESSION['cart']) || !count($_SESSION['cart'])) {
    $oPage->addStatusMessage(_('Cart is empty'));
    $oPage->redirect('pricelist.php');
}

$delete = $oPage->getRequestValue('delete', 'int');
if (!is_null($delete)) {
    $oPage->addStatusMessage(sprintf(_('Cart Item %s was removed'),
            $_SESSION['cart'][$delete]['nazev']));
    unset($_SESSION['cart'][$delete]);
    $oPage->redirect('cart.php');
}

$oPage->addToHeader(new ui\PageTop(_('Spoje.Net Order Form')));

$oPage->container->addItem(new ui\CartForm(isset($_SESSION['cart']) ? $_SESSION['cart']
            : []));

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
