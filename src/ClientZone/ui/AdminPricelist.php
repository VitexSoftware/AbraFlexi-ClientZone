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
class AdminPricelist extends \Ease\TWB5\Panel
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
        $items        = $this->pricer->getColumnsFromFlexibee('info',
            ['(in subtree '.$category.')']);

        parent::__construct(_('Pricelist'), 'info', null,
            new \Ease\TWB5\Badge('info',
            sprintf(_('%s items found'), count($items))));

        if (count($items)) {
            foreach ($items as $itemId => $item) {
                
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
        $row->addColumn(2, $pricelistItemData['cenaZakl']);
        $row->addColumn(2,
            new \Ease\TWB5\LinkButton('cenik.php?id='.$pricelistItemData['id'],
            _('Edit'), 'success'));

        return $row;
    }

}
