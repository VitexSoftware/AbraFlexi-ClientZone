<?php

declare(strict_types=1);

namespace ClientZone\ui;

/**
 * Listing of customer invoices / orders.
 *
 * @author vitex
 */
class OrdersListing extends \Ease\TWB5\Panel
{
    /**
     * @param \AbraFlexi\RO          $fetcher    Proforma/Invoice/Order fetcher
     * @param string                 $caption    Panel caption
     * @param array<string, mixed>   $conditions Query conditions
     */
    public function __construct(\AbraFlexi\RO $fetcher, string $caption, array $conditions = [])
    {
        $documents = $fetcher->getColumnsFromAbraFlexi('full', $conditions);
        $celkem = 0;
        parent::__construct($caption, 'info');
        if (!empty($documents)) {
            foreach ($documents as $orderData) {
                $this->addItem(new OrderListingItem($orderData, $fetcher->getEvidence()));
                $celkem += (float) ($orderData['sumCelkem'] ?? 0);
            }
            $footer = new \Ease\TWB5\Row();
            $footer->addColumn(2, (string) count($documents));
            $footer->addColumn(2, $celkem.' Kč');
            $this->footer->addItem($footer);
        }
    }
}
