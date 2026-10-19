<?php

declare(strict_types=1);

/**
 * clientzone - Shopping cart.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2026 VitexSoftware v.s.cz
 */

namespace ClientZone\ui;

class CartForm extends \Ease\Html\DivTag
{
    public ?\AbraFlexi\Cenik $product = null;

    /**
     * @param array<int|string, array<string, mixed>> $cartData
     */
    public function __construct(array $cartData = [])
    {
        parent::__construct(null, ['class' => 'cz-cart']);

        $head = $this->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-catalog-head']));
        $head->addItem(new \Ease\Html\DivTag(_('Checkout'), ['class' => 'eyebrow']));
        $head->addItem(new \Ease\Html\H2Tag(_('Cart')));

        if (!count($cartData)) {
            $empty = $this->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-empty']));
            $empty->addItem(new \Ease\Html\PTag(_('Cart is empty')));
            $empty->addItem(new \Ease\Html\ATag(
                'index.php#catalog',
                _('Browse catalog').' <span class="arrow">→</span>',
                ['class' => 'btn btn-primary'],
            ));

            return;
        }

        $this->product = new \AbraFlexi\Cenik();
        $list = $this->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-cart-list']));

        $price = 0.0;
        $count = 0;
        foreach ($cartData as $itemId => $cartItem) {
            $cartItem['id'] = $itemId;
            $qty = isset($cartItem['mnozMj']) ? (float) $cartItem['mnozMj'] : 1.0;
            $price += (float) $cartItem['cenaMj'] * $qty;
            $count += $qty;
            $list->addItem($this->itemRow($cartItem, $qty));
        }

        $footer = $this->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-cart-footer']));
        $footer->addItem(new \Ease\Html\DivTag(
            '<span class="dim">'._('Total').'</span>'
            .'<strong class="cz-price-main">'.number_format($price, 0, ',', ' ').' Kč</strong>'
            .'<span class="dim">'.sprintf(_('%s items'), (int) $count).'</span>',
            ['class' => 'cz-cart-total'],
        ));
        $footer->addItem(new \Ease\Html\ATag(
            'ordernow.php',
            _('Order now').' <span class="arrow">→</span>',
            ['class' => 'btn btn-glow btn-lg'],
        ));
    }

    /**
     * @param array<string, mixed> $cartItem
     */
    public function itemRow(array $cartItem, float $qty): \Ease\Html\DivTag
    {
        $row = new \Ease\Html\DivTag(null, ['class' => 'cz-cart-item']);
        $this->product->setData([
            'id' => $cartItem['icon'],
            'nazev' => $cartItem['nazev'],
        ]);
        $row->addItem(new ProductImageThumbnail($this->product, [
            'class' => 'cz-cart-thumb',
        ]));
        $meta = $row->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-cart-meta']));
        $meta->addItem(new \Ease\Html\DivTag((string) $cartItem['nazev'], ['class' => 'cz-cart-name']));
        $meta->addItem(new \Ease\Html\DivTag(
            sprintf(_('Qty: %s'), $qty == floor($qty) ? (string) (int) $qty : (string) $qty),
            ['class' => 'dim'],
        ));
        $row->addItem(new \Ease\Html\DivTag(
            number_format((float) $cartItem['cenaMj'] * $qty, 0, ',', ' ').' Kč',
            ['class' => 'cz-cart-price'],
        ));
        $row->addItem(new \Ease\Html\ATag(
            'cart.php?delete='.urlencode((string) $cartItem['id']),
            '<i class="fa fa-times" aria-hidden="true"></i> '._('Remove'),
            ['class' => 'btn btn-line btn-sm'],
        ));

        return $row;
    }
}
