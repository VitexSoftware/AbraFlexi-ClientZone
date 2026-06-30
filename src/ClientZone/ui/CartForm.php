<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ClientZone\ui;

/**
 * Description of CartForm
 *
 * @author vitex
 */
class CartForm extends \Ease\TWB5\Panel
{
    /**
     * Cenik
     * @var \AbraFlexi\Cenik
     */
    public $product = null;

    /**
     * Draw cart form
     *
     * @param array $cartData
     */
    public function __construct($cartData = [])
    {
        parent::__construct([new \Ease\Html\ImgTag('images/cart.svg', _('Cart'),
                ['width' => 50]), _('Cart contents')], 'info');
        if (count($cartData)) {
            $this->product = new \AbraFlexi\Cenik();
            $itemRow       = new \Ease\TWB5\Row();
            $itemRow->addColumn(1, _('Image'));
            $itemRow->addColumn(6, _('Name'));
            $itemRow->addColumn(2, _('Price'));
            $itemRow->addColumn(1, _('Count'));
            $itemRow->addColumn(2, _('Remove'));
            $this->addItem($itemRow);
            $price         = 0;
            $count         = 0;
            foreach ($cartData as $itemId => $cartItem) {
                $cartItem['id'] = $itemId;
                $price          += $cartItem['cenaMj'];
                $count          += isset($cartItem['mnozMj']) ? $cartItem['mnozMj']
                        : 1;
                $this->addItem($this->itemRow($cartItem));
            }

            $this->addToFooter = new \Ease\TWB5\Row();

            $this->addToFooter->addColumn(7,
                new \Ease\TWB5\LinkButton('ordernow.php',
                [new \Ease\Html\ImgTag('images/cashdesk.svg', _('Cash desk'),
                    ['height' => '50px']), _('Order now')], 'success btn-block'));
            $this->addToFooter->addColumn(2, round($price));
            $this->addToFooter->addColumn(1, $count);
        } else {
            $this->addItem(new \Ease\TWB5\Badge('warning', _('Cart is empty')));
        }
    }

    /**
     * Row in cart
     *
     * @param array $cartItem
     * @return \Ease\TWB5\Row
     */
    public function itemRow($cartItem)
    {
        $count   = isset($cartItem['mnozMj']) ? $cartItem['mnozMj'] : 1;
        $itemRow = new \Ease\TWB5\Row();
        $this->product->setData(['id' => $cartItem['icon'], 'nazev' => $cartItem['nazev']]);
        $itemRow->addColumn(1,
            new ProductImageThumbnail($this->product,
            ['class' => 'thumbnail', 'style' => 'height: 40px']));
        $itemRow->addColumn(6, $cartItem['nazev']);
        $itemRow->addColumn(2, round($cartItem['cenaMj'] * $count));
        $itemRow->addColumn(1, $count);
        $itemRow->addColumn(2,
            new \Ease\TWB5\LinkButton('cart.php?delete='.$cartItem['id'],
            '<i class="fa fa-times"></i>', 'danger'));
        return $itemRow;
    }

}
