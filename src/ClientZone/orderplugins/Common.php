<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ClientZone\orderplugins;

/**
 * Description of Common
 *
 * @author vitex
 */
class Common extends \ClientZone\OrderPlugin
{
    /**
     * Name of Product/Service
     * @var string
     */
    public $name = null;

    /**
     * FlexiBee storage item Code
     * @var string
     */
    public $productCode = null;

    /**
     *
     * @var type
     */
    public $fields = [];

    public function __construct()
    {
        $this->name = _('Common item');
    }

    /**
     * Add Product Fields to Form
     *
     * @param Ease\TWB5\Form $form
     * @return Ease\TWB5\Form
     */
    public function formFields($form)
    {
        parent::formFields($form);

        $code = $form->order->getDataValue('kod') ? $form->order->getDataValue('kod')
                : '';

        $count = $form->order->getDataValue('count') ? $form->order->getDataValue('count')
                : 1;

        $form->addInput(new \Ease\Html\InputTextTag('kod', $code),
            _('Product Code'), _('ANTENA_2ODB_OUTDOOR'),
            _('Product code from our pricelist'));

        $form->addInput(new \Ease\Html\InputNumberTag('count', $count),
            _('Items count'), '', _('Amount requested'));

        $form->addInput(new \Ease\Html\InputTextTag('poznam',
            $form->order->getDataValue('note')), _('Note for stuff'),
            _('ASAP Please'), _('Your kind words about ordered item'));

        return $form;
    }

    /**
     * Control Plugin fields
     *
     * @param \ClientZone\OrderItem $order
     * @return boolean
     */
    public function controlFields($order): bool
    {
        return parent::controlFields($order) && $this->checkCode($order->getDataValue('kod'))
            && $this->checkCount($order->getDataValue('count'));
    }

    /**
     * Check Count
     *
     * @param int $count 
     * @return boolen check result
     */
    public function checkCount($count)
    {
        return $count > 0;
    }

    /**
     * Check FlexiBee Product Code
     *
     * @param string $number VoIP Number
     * @return boolean
     */
    public function checkCode($code)
    {
        $ok = true;
        if (empty($code)) {
            $ok = false;
        } else {
            $pricelister = new \AbraFlexi\Cenik('code:'.$code);
            if (empty($pricelister->getMyKey())) {
                $this->addStatusMessage(sprintf(_('Producet with code %s does not exist'),
                        $code), 'warning');
            } else {
                $ok = true;
            }
        }
        return $ok;
    }

    /**
     * Make OrderItem from FormData
     *
     * @param Order $order
     * @return array
     */
    public function processFields($order): array
    {
        $orderItemData = parent::processFields($order);
        $orderItemData['mnozMj'] = $order->getDataValue('count');
        $orderItemData['poznam'] = $order->getDataValue('poznam');
        $orderItemData['cenik'] = 'code:'.$order->getDataValue('kod');

        return $orderItemData;
    }

}
