<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ClientZone\ui;

/**
 * Description of Pricelist
 *
 * @author vitex
 */
class Pricelist extends \Ease\TWB5\Panel
{
    /**
     *
     * @var \AbraFlexi\Cenik
     */
    public $pricer = null;

    public function __construct($category, $labels = [])
    {
        $order     = new \ClientZone\OrderItem();
        $orderable = [];
        foreach ($order->plugins as $pluginName => $plugin) {
            $orderable[$plugin->productCode] = $pluginName;
        }

        if (count($labels)) {
            foreach ($labels as $lid => $label) {
                $labels[$lid] = 'stitky=\'code:'.$label.'\'';
            }
            $labelCond = implode(' and ', $labels);
            $category  .= ' and '.$labelCond;
        }

        $this->pricer = new \AbraFlexi\Cenik();
        $items        = $this->pricer->getColumnsFromAbraFlexi('info', ['(in subtree '.$category.')']);

        parent::__construct(
            _('Pricelist'),
            'info',
            null,
            new \Ease\TWB5\Badge(sprintf(_('%s items found'), count($items)), 'info'),
        );

        if (count($items)) {
            $headerRow = new \Ease\TWB5\Row();
            $headerRow->addColumn(1);
            $headerRow->addColumn(2, new \Ease\TWB5\Badge(_('Item name'), 'info'));
            $headerRow->addColumn(2, new \Ease\TWB5\Badge(_('Price without VAT'), 'info'));
            $headerRow->addColumn(2, new \Ease\TWB5\Badge(_('Price with VAT'), 'info'));
            $headerRow->addColumn(2, new \Ease\TWB5\Badge(_('Actions'), 'info'));
            $this->addItem($headerRow);
            $this->addItem(new \Ease\Html\HrTag());
            foreach ($items as $item) {

                if (array_key_exists($item['kod'], $orderable)) {
                    $item['service'] = $orderable[$item['kod']];
                } else {
                    $item['service'] = 'Common&kod='.$item['kod'];
                }

                $this->addItem($this->pricelistRow($item));
            }
        }
    }

    public function pricelistRow($pricelistItemData)
    {
        $row = new \Ease\TWB5\Row();
        $this->pricer->setMyKey($pricelistItemData['id']);
        $row->addColumn(1,
            new ProductImageThumbnail($this->pricer,
            ['class' => 'thumbnail', 'style' => 'height: 40px']));
        $row->addColumn(2, $pricelistItemData['nazev']);
        $row->addColumn(2, $pricelistItemData['cenaZaklBezDph']);
        $row->addColumn(2, round($pricelistItemData['cenaZaklVcDph']));
        if (array_key_exists('service', $pricelistItemData)) {
            $isAnon   = get_class(\Ease\Shared::user()) === 'Ease\Anonym';
            $orderUrl = $isAnon
                ? 'login.php?backurl='.urlencode('orderform.php?service='.$pricelistItemData['service'])
                : 'orderform.php?service='.$pricelistItemData['service'];
            $row->addColumn(2,
                new \Ease\TWB5\LinkButton($orderUrl, _('Order Now'), 'success'));
        }

        return $row;
    }
}
