<?php

declare(strict_types=1);

/**
 * clientzone - OrderPlugin parent.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2026 VitexSoftware v.s.cz
 */

namespace ClientZone;

/**
 * Base class for order form plugins (VoIP credit, domain credit, …).
 */
abstract class OrderPlugin extends \Ease\Sand
{
    /**
     * Name of Product/Service.
     */
    public $name = null;

    /**
     * AbraFlexi pricelist item code.
     */
    public $productCode = null;

    /**
     * Extra form field definitions.
     *
     * @var array
     */
    public $fields = [];

    /**
     * When set, offer this plugin only to customers with matching ext:prefix ID.
     * Examples: ipex, subreg.
     */
    public ?string $reqExtPrefix = null;

    /**
     * Add product fields to the order form.
     *
     * @param \ClientZone\ui\OrderFormHtml $form
     *
     * @return \ClientZone\ui\OrderFormHtml
     */
    public function formFields($form)
    {
        $form->addItem(new \Ease\Html\InputHiddenTag('code', $this->productCode));

        return $form;
    }

    /**
     * Make OrderItem payload from form data.
     *
     * @param OrderItem $order
     *
     * @return array<string, mixed>
     */
    /**
     * @return array<string, mixed>
     */
    public function processFields($order): array
    {
        $cenaMj = $order->cenik->getDataValue('cenaZakl');
        if (empty($cenaMj)) {
            $cenaMj = $order->cenik->getDataValue('cenaZaklVcDph');
        }

        return [
            'cenaMj' => (float) $cenaMj,
            'nazev' => $order->cenik->getDataValue('nazev'),
            'cenik' => 'code:'.$this->productCode,
            'typPolozkyK' => 'typPolozky.katalog',
        ];
    }

    /**
     * Validate required fields.
     *
     * @param OrderItem $order
     */
    public function controlFields($order): bool
    {
        if (!strlen((string) $order->getDataValue('service'))) {
            $order->addStatusMessage(_('Product not specified'), 'error');

            return false;
        }

        return true;
    }

    /**
     * Customer must be signed in.
     */
    public function checkLogin(): bool
    {
        if (!(\Ease\Shared::user() instanceof Customer)) {
            $this->addStatusMessage(_('Please sign in to order item'), 'warning');

            return false;
        }

        return true;
    }

    /**
     * Called when an invoice item was settled (usually handled externally).
     *
     * @return bool|null
     */
    public function settled()
    {
        $this->addStatusMessage(
            sprintf(_('Item %s was settled without action handled in OrderPlugin code'), $this->productCode),
            'warning',
        );

        return null;
    }
}
