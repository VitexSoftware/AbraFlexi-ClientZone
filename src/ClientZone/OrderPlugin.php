<?php
/**
 * clientzone - OrderPlugin parent.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017 VitexSoftware v.s.cz
 */

namespace ClientZone;

/**
 * Description of OrderPlugin
 *
 * @author vitex
 */
abstract class OrderPlugin extends \Ease\Sand
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

    /**
     * Add Product Fields to Form
     *
     * @param Ease\TWB5\Form $form
     * @return Ease\TWB5\Form
     */
    public function formFields($form)
    {
        $form->addItem(new \Ease\Html\InputHiddenTag('code', $this->productCode));
        return $form;
    }

    /**
     * Make OrderItem from FormData
     *
     * @param OrderItem $order
     * @return array
     */
    public function processFields($order)
    {
        $orderItemData = [
            'cenaMj' => (float) $order->cenik->getDataValue('cenaZaklVcDph'),
            'nazev' => $order->cenik->getDataValue('nazev'),
            'cenik' => 'code:'.$this->productCode,
            'typPolozkyK' => 'typPolozky.katalog'
        ];
        return $orderItemData;
    }

    /**
     * Control filelds for requirements
     *
     * @param OrderItem $order
     * @return boolean
     */
    public function controlFields($order)
    {
        $result = true;
        if (!strlen($order->getDataValue('service'))) {
            $order->addStatusMessage(_('Product not specified'), 'error');
            $result = false;
        }
        return $result;
    }

    /**
     * Method called when the item was settled
     *
     * @return boolean|null Processing result
     */
    public function settled()
    {
        $this->addStatusMessage(sprintf(_('Item %s was settled without action handled in OrderPlugin code'),
                $this->productCode), 'warning');
        return null;
    }
}
