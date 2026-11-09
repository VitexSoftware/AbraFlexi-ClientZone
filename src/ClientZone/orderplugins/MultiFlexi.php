<?php

declare(strict_types=1);

/**
 * ClientZone - MultiFlexi SaaS order plugin.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2026 VitexSoftware
 */

namespace ClientZone\orderplugins;

/**
 * Order a MultiFlexi SaaS instance (product MULTIFLEXI).
 *
 * Provisioning (MultiFlexi account + AbraFlexi contract) runs automatically
 * after the invoice is paid: the settled event triggers a Semaphore task.
 */
class MultiFlexi extends \ClientZone\OrderPlugin
{
    public $name = null;

    public $productCode = 'MULTIFLEXI';

    public function __construct()
    {
        $this->name = _('MultiFlexi (SaaS)');
    }

    /**
     * @param \ClientZone\ui\OrderFormHtml $form
     *
     * @return \ClientZone\ui\OrderFormHtml
     */
    public function formFields($form)
    {
        parent::formFields($form);

        $form->addInput(
            new \Ease\Html\InputNumberTag(
                'quantity',
                $form->order->getDataValue('quantity') ?: 1,
                ['min' => '1', 'step' => '1', 'required' => 'required'],
            ),
            _('Instances'),
            '1',
            _('Number of MultiFlexi instances'),
        );

        return $form;
    }

    /**
     * @param \ClientZone\OrderItem $order
     */
    public function controlFields($order): bool
    {
        return parent::controlFields($order)
            && $this->checkLogin()
            && $this->checkQuantity($order->getDataValue('quantity'));
    }

    public function checkQuantity(mixed $quantity): bool
    {
        $value = (int) $quantity;

        if ($value < 1) {
            $this->addStatusMessage(_('Please enter at least 1 instance'), 'warning');

            return false;
        }

        return true;
    }

    public function processFields($order): array
    {
        $orderItemData = parent::processFields($order);
        $quantity = (int) ($order->getDataValue('quantity') ?: 1);
        $orderItemData['mnozMj'] = (float) $quantity;
        $orderItemData['nazev'] = _('MultiFlexi (SaaS)').': '.$quantity.'x';
        $orderItemData['typZasobyK'] = 'typZasoby.poplatek';
        $orderItemData['stitky'] = 'MultiFlexi,SaaS';

        return $orderItemData;
    }

    /**
     * Provisioning is done by MultiFlexi after payment (faktura-vydana/settled
     * event triggers a Semaphore task which creates the account and contract).
     */
    public function settled()
    {
        $this->addStatusMessage(
            sprintf(_('Item %s will be provisioned by MultiFlexi after payment'), $this->productCode),
            'info',
        );

        return true;
    }
}
