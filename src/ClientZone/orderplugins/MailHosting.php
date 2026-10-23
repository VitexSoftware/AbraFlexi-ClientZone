<?php

declare(strict_types=1);

/**
 * ClientZone - e-mail hosting order plugin.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2026 VitexSoftware
 */

namespace ClientZone\orderplugins;

/**
 * Order e-mail hosting for a domain, one unit per mailbox (product HOSTING_MAIL).
 */
class MailHosting extends \ClientZone\OrderPlugin
{
    public $name = null;

    public $productCode = 'HOSTING_MAIL';

    public function __construct()
    {
        $this->name = _('Mail hosting');
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
            _('Domain the mailboxes will belong to'),
        );

        $form->addInput(
            new \Ease\Html\InputNumberTag('mailboxes', $form->order->getDataValue('mailboxes') ?: 1, ['min' => '1', 'step' => '1', 'required' => 'required']),
            _('Mailboxes'),
            '1',
            _('Number of mailboxes requested'),
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
            && $this->checkDomain($order->getDataValue('domain'))
            && $this->checkMailboxes($order->getDataValue('mailboxes'));
    }

    public function checkDomain(mixed $domain): bool
    {
        if (filter_var((string) $domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
            $this->addStatusMessage(_('Please enter a valid domain'), 'warning');

            return false;
        }

        return true;
    }

    public function checkMailboxes(mixed $mailboxes): bool
    {
        if ((int) $mailboxes < 1) {
            $this->addStatusMessage(_('Please enter at least one mailbox'), 'warning');

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
        $orderItemData['mnozMj'] = (float) $order->getDataValue('mailboxes');
        $orderItemData['nazev'] = _('Mail hosting').': '.$domain;
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
