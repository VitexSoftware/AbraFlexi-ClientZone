<?php

declare(strict_types=1);

/**
 * ClientZone - Subreg domain credit order plugin.
 *
 * Settlement of paid invoices is handled by abraflexi-domain-credit (MultiFlexi).
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2026 VitexSoftware / Spoje.Net
 */

namespace ClientZone\orderplugins;

/**
 * Order Subreg prepaid domain credit for the logged-in customer (ext:subreg).
 */
class DomainCredit extends \ClientZone\OrderPlugin
{
    public $name = null;

    public $productCode = 'KREDIT_DOMENA';

    /**
     * Offer only to customers with ext:subreg:…
     */
    public ?string $reqExtPrefix = 'subreg';

    /**
     * Minimal credit amount (quantity of KREDIT_DOMENA units).
     */
    public float $minCredit = 500.0;

    public function __construct()
    {
        $this->name = _('Domain Credit');
    }

    /**
     * @param \ClientZone\ui\OrderFormHtml $form
     *
     * @return \ClientZone\ui\OrderFormHtml
     */
    public function formFields($form)
    {
        parent::formFields($form);

        $credit = $form->order->getDataValue('credit') ?: $this->minCredit;

        $form->addInput(
            new \Ease\Html\InputNumberTag(
                'credit',
                $credit,
                ['min' => $this->minCredit, 'step' => '1', 'required' => 'required'],
            ),
            _('Required Credit Value'),
            (string) $this->minCredit,
            _('Amount of Credit you requested'),
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
            && $this->checkCredit($order->getDataValue('credit'));
    }

    public function checkCredit(mixed $credit): bool
    {
        $value = (float) $credit;
        if ($value <= 0) {
            $this->addStatusMessage(_('Please enter Requested Credit'), 'warning');

            return false;
        }
        if ($value < $this->minCredit) {
            $this->addStatusMessage(
                sprintf(_('Please enter at least %s Credit'), $this->minCredit),
                'warning',
            );

            return false;
        }

        return true;
    }

    /**
     * @param \ClientZone\OrderItem $order
     *
     * @return array<string, mixed>
     */
    public function processFields($order): array
    {
        $orderItemData = parent::processFields($order);
        // Subreg credit amount = mnozMj * cenaMj; cenaZakl is 1 Kč per unit
        $orderItemData['cenaMj'] = (float) ($order->cenik->getDataValue('cenaZakl') ?: 1);
        $orderItemData['mnozMj'] = (float) $order->getDataValue('credit');
        $orderItemData['nazev'] = _('Domain Credit').': '.$orderItemData['mnozMj'];
        $orderItemData['typZasobyK'] = 'typZasoby.poplatek';
        $orderItemData['stitky'] = 'API';

        return $orderItemData;
    }

    /**
     * Fulfilment is done by abraflexi-domain-credit after payment.
     */
    public function settled()
    {
        $this->addStatusMessage(
            sprintf(_('Item %s will be settled by abraflexi-domain-credit after payment'), $this->productCode),
            'info',
        );

        return true;
    }
}
