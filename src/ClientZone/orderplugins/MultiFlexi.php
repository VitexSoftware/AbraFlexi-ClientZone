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

    /**
     * Paid for: every period until the contract ends.
     */
    public ?string $billing = \ClientZone\BillingKind::RECURRING;

    public function __construct()
    {
        $this->name = _('MultiFlexi (SaaS)');
    }

    /**
     * @param \ClientZone\OrderItem $order
     */
    public function controlFields($order): bool
    {
        return parent::controlFields($order) && $this->checkLogin();
    }

    public function processFields($order): array
    {
        $orderItemData = parent::processFields($order);
        // One service per order: the number of instances is not chosen here.
        $orderItemData['mnozMj'] = 1.0;
        $orderItemData['nazev'] = _('MultiFlexi (SaaS)');
        $orderItemData['typZasobyK'] = 'typZasoby.poplatek';
        // Only labels that exist in AbraFlexi (as the other plugins): 'MultiFlexi' and 'SaaS' do not, and AbraFlexi refuses the document.
        $orderItemData['stitky'] = 'API';

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
