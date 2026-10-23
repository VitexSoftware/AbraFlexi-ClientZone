<?php

declare(strict_types=1);

/**
 * ClientZone - web hosting order plugin.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2026 VitexSoftware
 */

namespace ClientZone\orderplugins;

/**
 * Order web hosting subscription for a domain (product HOSTING_WEB).
 */
class WebHosting extends \ClientZone\OrderPlugin
{
    public $name = null;

    public $productCode = 'HOSTING_WEB';

    public function __construct()
    {
        $this->name = _('Web hosting');
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
            new \Ease\Html\InputTextTag('domain', $form->order->getDataValue('domain'), ['required' => 'required']),
            _('Domain'),
            'example.com',
            _('Domain the web hosting will serve'),
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
            && $this->checkDomain($order->getDataValue('domain'));
    }

    public function checkDomain(mixed $domain): bool
    {
        if (filter_var((string) $domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
            $this->addStatusMessage(_('Please enter a valid domain'), 'warning');

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
        $domain = (string) $order->getDataValue('domain');
        $orderItemData['nazev'] = _('Web hosting').': '.$domain;
        $orderItemData['poznam'] = $domain;

        return $orderItemData;
    }

    /**
     * Provisioning is done by MultiFlexi after payment.
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
