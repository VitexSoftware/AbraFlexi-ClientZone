<?php

declare(strict_types=1);

/**
 * clientzone - Přehled Košíku.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2026 VitexSoftware v.s.cz
 */

namespace ClientZone;

require_once 'includes/Init.php';

$delete = $oPage->getRequestValue('delete', 'int');
if ($delete !== null && isset($_SESSION['cart'][$delete])) {
    $oPage->addStatusMessage(sprintf(
        _('Cart Item %s was removed'),
        $_SESSION['cart'][$delete]['nazev'],
    ));
    unset($_SESSION['cart'][$delete]);
    $oPage->redirect('cart.php');
    exit;
}

$oPage->addToHeader(new ui\PageTop(_('Cart')));
$oPage->container->addItem(new ui\CartForm($_SESSION['cart'] ?? []));
$oPage->addItem(new ui\PageBottom());
$oPage->draw();
