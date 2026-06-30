<?php

namespace ClientZone\ui;

/**
 * clientzone - OrderForm class
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017 VitexSoftware v.s.cz
 */
class OrderFormHtml extends \Ease\TWB5\Form
{
    /**
     * Order object
     * @var Order
     */
    public $order = null;

    /**
     * Credit Request Form
     *
     * @param \ClientZone\OrderItem $order    Form Identificaton
     */
    public function __construct($order)
    {
        $this->order = $order;
        parent::__construct('OrderForm');
    }

    /**
     * Draw Form
     */
    public function finalize()
    {
        $service = $this->order->getDataValue('service');
        $this->addInput($this->productList(), _('Service Requested'));
        if (empty($service)) {
            $this->addItem(new \Ease\TWB5\SubmitButton(_('Enter details'),
                'success'));
        } else {
            if (is_object($this->order->plugins[$service])) {
                $this->order->plugins[$service]->formFields($this);
            }
            $this->addItem(new \Ease\TWB5\SubmitButton(_('Order Now'), 'success'));
        }
        parent::finalize();
    }

    /**
     * Product list select
     * 
     * @return \Ease\Html\Select
     */
    public function productList()
    {
        $serviceMenu = ['' => _('Please choose your service')];

        foreach ($this->order->plugins as $className => $itemclass) {
            $serviceMenu[$className] = $itemclass->name;
        }

        /*
          'PROVOZVOIPTELEFONU' => _('VoIP'),
          'KREDIT_DOMENY' => _('Domain Credit'),
         */
        return new \Ease\Html\Select('service', $serviceMenu,
            $this->order->getDataValue('service'));
    }

}
