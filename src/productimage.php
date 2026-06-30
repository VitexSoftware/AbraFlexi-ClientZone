<?php
/**
 * clientzone - Obrázek produktu.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017 VitexSoftware v.s.cz
 */
namespace ClientZone;

require_once 'includes/Init.php';

$id = $oPage->getRequestValue('id');
if (is_null($id)) {
    die(_('Missing product ID'));
}
$product = new \AbraFlexi\Cenik();
$product->setMyKey($id);
$img     = $oPage->addItem(new ui\ProductImage($product));
$img->setTagCss(['display' => 'block', 'margin' => '0 auto;']);
$oPage->draw();
