<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ClientZone\ui;

/**
 * Description of OrdersListing
 *
 * @author vitex
 */
class OrdersListing extends \Ease\TWB5\Panel
{

    /**
     * Orders Listing
     *
     * @param \AbraFlexi\RO $fetcher     Proforma/Invoice/Order
     * @param array                  $conditions  Conditions
     * @param string                 $caption     Panel caption
     */
    public function __construct($fetcher, $conditions = [], $caption)
    {
        $documents = $fetcher->getColumnsFromFlexiBee('full', $conditions);
        $celkem    = 0;
        parent::__construct($caption, 'info');
        if (!empty($documents)) {
            foreach ($documents as $orderData) {
                $this->addItem(new OrderListingItem($orderData,
                    $fetcher->getEvidence()));
                $celkem += $orderData['sumCelkem'];
            }
            $this->addToFooter = new \Ease\TWB5\Row();
            $this->addToFooter->addColumn('2', count($documents));
            $this->addToFooter->addColumn('2', $celkem.' Kč');
        }
    }

}
