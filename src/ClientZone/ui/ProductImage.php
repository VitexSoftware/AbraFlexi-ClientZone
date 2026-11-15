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
class ProductImage extends \Ease\Html\ImgTag
{

    public function __construct($product, $type = 'image', $tagProperties = [])
    {
        parent::__construct(
            'prdimg.php?type='.$type.'&id='.$product->getMyKey(),
            // the name comes from AbraFlexi and Ease does not escape attributes
            htmlspecialchars((string) ($product->getDataValue('nazev') ?? ''), \ENT_QUOTES),
            $tagProperties,
        );
    }

}
