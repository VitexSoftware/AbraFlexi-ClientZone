<?php

declare(strict_types=1);

namespace ClientZone\ui;

/**
 * clientzone - OrderForm class.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2026 VitexSoftware v.s.cz
 */
class OrderFormHtml extends \Ease\TWB5\Form
{
    /**
     * Order item being edited.
     *
     * @var \ClientZone\OrderItem
     */
    public $order = null;

    /**
     * Credit / service request form.
     *
     * @param \ClientZone\OrderItem $order
     */
    public function __construct($order)
    {
        $this->order = $order;
        parent::__construct(['name' => 'OrderForm', 'method' => 'post']);
    }

    public function finalize(): void
    {
        $service = $this->order->getDataValue('service');
        $this->addInput($this->productList(), _('Service Requested'));

        if (empty($service)) {
            $this->addItem(new \Ease\TWB5\SubmitButton(_('Enter details'), 'success'));
        } else {
            if (isset($this->order->plugins[$service]) && is_object($this->order->plugins[$service])) {
                $plugin = $this->order->plugins[$service];
                if (empty($this->order->cenik->getMyKey()) && !empty($plugin->productCode)) {
                    $this->order->cenik->ignore404(true);
                    $this->order->cenik->loadFromAbraFlexi('code:'.$plugin->productCode);
                    $this->order->cenik->ignore404(false);
                }
                $plugin->formFields($this);
            }
            $this->addItem(new \Ease\TWB5\SubmitButton(_('Order Now'), 'success'));
        }

        parent::finalize();
    }

    /**
     * Product list select — filtered by customer ext:ipex / ext:subreg when required.
     */
    public function productList(): \Ease\Html\SelectTag
    {
        $serviceMenu = ['' => _('Please choose your service')];
        $user = \Ease\Shared::user();

        foreach ($this->order->plugins as $className => $itemclass) {
            if (!empty($itemclass->reqExtPrefix)) {
                // VoIP / Subreg credit: only for signed-in customers with matching ext ID
                if (!($user instanceof \ClientZone\Customer)) {
                    continue;
                }
                $prefixID = $user->getAdresar()->getExternalID($itemclass->reqExtPrefix);
                if (empty($prefixID)) {
                    continue;
                }
            }
            $serviceMenu[$className] = $itemclass->name;
        }

        return new \Ease\Html\SelectTag(
            'service',
            $serviceMenu,
            (string) $this->order->getDataValue('service'),
        );
    }
}
