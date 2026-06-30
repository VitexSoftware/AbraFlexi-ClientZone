<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ClientZone\ui;

/**
 * Description of ProductImage
 *
 * @author vitex
 */
class ProductImageThumbnail extends \Ease\Html\ATag
{
    public $product = [];

    public function __construct($product, $tagProperties = [])
    {
        $this->product['id'] = $product->getMyKey();
        parent::__construct('productimage.php?id='.$product,
            new ProductImage($product, 'thumbnail', $tagProperties));
    }

    public function finalize(): void
    {
        $imager = new \ClientZone\ProductImager($this->product['id']);
        if ($imager->imageExists('image', 'jpg') || $imager->imageExists('image',
                'png') || $imager->imageExists('image', 'svg')) {
            $this->includeJavaScript('js/jquery.magnific-popup.min.js');
            $this->includeCss('css/magnific-popup.css');
            $this->addTagClass('ajax-popup-link');
            $this->addJavaScript("$('.ajax-popup-link').magnificPopup({type:'ajax'});");
        }

        parent::finalize();
    }

}
