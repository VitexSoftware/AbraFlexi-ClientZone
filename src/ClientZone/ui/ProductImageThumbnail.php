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
        parent::__construct(
            'productimage.php?id='.$this->product['id'],
            new ProductImage($product, 'thumbnail', $tagProperties),
        );
    }

    public function finalize(): void
    {
        $imager = new \ClientZone\ProductImager($this->product['id']);
        if ($imager->imageExists('image', 'jpg') || $imager->imageExists('image',
                'png') || $imager->imageExists('image', 'svg')) {
            // The plugin needs jQuery loaded before it; without it the script fails and the link opens the image page instead.
            \Ease\Part::jQueryze();
            $this->includeJavaScript('js/jquery.magnific-popup.min.js');
            $this->includeCss('css/magnific-popup.css');
            // The link points at the picture itself: the popup shows it enlarged, and without JavaScript the browser opens just the picture.
            $this->setTagProperties(['href' => 'prdimg.php?type=image&id='.rawurlencode((string) $this->product['id'])]);
            $this->addTagClass('image-popup-link');
            $this->addJavaScript("$('.image-popup-link').magnificPopup({type:'image', closeOnContentClick:true});");
        }

        parent::finalize();
    }

}
