<?php

declare(strict_types=1);

/**
 * clientzone - Unpaid invoices with QR payment.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2018-2026 VitexSoftware v.s.cz
 */

namespace ClientZone\ui;

class ToPayItems extends \Ease\Html\DivTag
{
    /**
     * @param \ClientZone\Customer $customer
     */
    public function __construct($customer)
    {
        parent::__construct(null, ['class' => 'cz-topay']);

        $debts = $customer->getCustomerDebts();
        if (empty($debts)) {
            return;
        }

        $head = $this->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-catalog-head']));
        $head->addItem(new \Ease\Html\DivTag(_('Payments'), ['class' => 'eyebrow']));
        $head->addItem(new \Ease\Html\H2Tag(_('Orders to pay')));

        $grid = $this->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-topay-grid']));

        foreach ($debts as $invoiceId => $invoiceInfo) {
            $customer->invoicer->setMyKey((int) $invoiceInfo['id']);
            $card = $grid->addItem(new \Ease\Html\ATag(
                'document.php?id='.(int) $invoiceInfo['id']
                    .'&embed=true&evidence='.$customer->invoicer->getEvidence(),
                null,
                ['class' => 'cz-topay-card'],
            ));
            $card->addItem(new \Ease\Html\ImgTag(
                $customer->invoicer->getQrCodeBase64(200),
                $invoiceInfo['kod'],
                ['class' => 'cz-topay-qr'],
            ));
            $card->addItem(new \Ease\Html\DivTag(
                $invoiceInfo['kod'],
                ['class' => 'cz-product-sku'],
            ));
            $card->addItem(new \Ease\Html\DivTag(
                '<strong>'.$invoiceInfo['sumCelkem'].'</strong> '
                .\AbraFlexi\Functions::uncode((string) $invoiceInfo['mena']),
                ['class' => 'cz-topay-amount'],
            ));
        }
    }
}
