<?php

declare(strict_types=1);

/**
 * ClientZone - VoIP IPEX credit order plugin.
 *
 * Settlement of paid invoices is handled by abraflexi-voip-credit (MultiFlexi).
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2026 VitexSoftware / Spoje.Net
 */

namespace ClientZone\orderplugins;

/**
 * Order prepaid VoIP credit for an IPEX number owned by the logged-in customer.
 */
class VoIPcredit extends \ClientZone\OrderPlugin
{
    public $name = null;

    public $productCode = 'KREDIT_VOIP';

    /**
     * Minimal requested credit (with VAT), overridden from cenik cenaZakl.
     */
    public float $minCredit = 200.0;

    /**
     * Offer only to customers with ext:ipex:…
     */
    public ?string $reqExtPrefix = 'ipex';

    private ?int $ipexUserID = null;

    private ?string $customerEmail = null;

    public function __construct()
    {
        $this->name = _('VoIP Credit');
    }

    /**
     * @param \ClientZone\ui\OrderFormHtml $form
     *
     * @return \ClientZone\ui\OrderFormHtml
     */
    public function formFields($form)
    {
        parent::formFields($form);

        $cenaZakl = $form->order->cenik->getDataValue('cenaZakl');
        if (!empty($cenaZakl)) {
            $this->minCredit = (float) $cenaZakl;
        }

        $credit = $form->order->getDataValue('credit') ?: $this->minCredit;

        $form->addInput(
            new \Ease\Html\InputNumberTag(
                'credit',
                $credit,
                ['min' => $this->minCredit, 'id' => 'credit', 'step' => '1', 'required' => 'required'],
            ),
            _('Required Credit Value').' ('._('with VAT').')',
            (string) $this->minCredit,
            _('Your price').' '._('without VAT').':  '.new \Ease\Html\SpanTag(null, ['id' => 'vatPrice']),
        );

        \Ease\WebPage::singleton()->addJavaScript(<<<'JS'
$('#credit').change(function(){
    var coeficient = 21 / 121;
    var price = $(this).val();
    var dph = coeficient * price;
    $('#vatPrice').html((price - dph).toFixed(2));
}).change();
JS);

        $form->addInput(
            new \Ease\Html\InputTextTag(
                'number',
                $form->order->getDataValue('number'),
                [
                    'pattern' => '\+?[0-9]{6,15}',
                    'required' => 'required',
                    'title' => sprintf(_('%d characters minimum'), 6),
                ],
            ),
            _('For Number'),
            '',
            _('Telephone number you requested to pay'),
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
            && $this->checkCredit($order->getDataValue('credit'))
            && $this->checkNumber($order->getDataValue('number'));
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
     * Validate that the number is an active prepaid IPEX number of the customer.
     */
    public function checkNumber(mixed $number): bool
    {
        $number = is_string($number) ? trim($number) : '';
        if ($number === '') {
            $this->addStatusMessage(_('VoIP Number not set'), 'warning');

            return false;
        }

        if (!class_exists(\IPEXB2B\Services::class)) {
            $this->addStatusMessage(_('IPEX library is not available'), 'error');

            return false;
        }

        $servicer = new \IPEXB2B\Services();
        $servicer->ignore404(true);
        $servicer->loadFromIPEX(['number' => $number]);
        $servicer->ignore404(false);
        $numberInfoRaw = $servicer->getData();

        if (empty($numberInfoRaw) || !is_array($numberInfoRaw)) {
            $this->addStatusMessage(sprintf(_('VoIP Number %s is not registered'), $number), 'warning');

            return false;
        }

        $normalized = ltrim($number, '+');
        $exact = array_values(array_filter(
            $numberInfoRaw,
            static fn ($record): bool => is_array($record)
                && ltrim((string) ($record['number'] ?? ''), '+') === $normalized,
        ));

        if (count($exact) !== 1) {
            $this->addStatusMessage(sprintf(_('VoIP Number %s is not registered'), $number), 'warning');

            return false;
        }

        $numberDetails = $exact[0];

        if (($numberDetails['paymentType'] ?? null) !== 'prepaid'
            || ($numberDetails['status'] ?? null) !== 'active') {
            $this->addStatusMessage(
                sprintf(_('Cannot add credit to VoIP Number %s'), $number),
                'warning',
            );

            return false;
        }

        $user = \Ease\Shared::user();
        if (!($user instanceof \ClientZone\Customer)) {
            $this->addStatusMessage(_('Please sign in to order item'), 'warning');

            return false;
        }

        $myCompany = $user->getAdresar();
        $owner = false;

        if (!empty($numberDetails['customerExternId'])) {
            $this->customerEmail = $user->getUserEmail();
            $this->ipexUserID = (int) $numberDetails['customerId'];
            $owner = \AbraFlexi\Functions::uncode((string) $numberDetails['customerExternId'])
                === \AbraFlexi\Functions::uncode((string) $myCompany->getDataValue('kod'));
        } else {
            $owner = $this->ipexCustomerNameToAbraFlexiCustomer((string) ($numberDetails['customerName'] ?? ''))
                === $myCompany->getDataValue('nazev');
            if ($owner) {
                $this->ipexUserID = (int) ($numberDetails['customerId'] ?? 0);
                $this->customerEmail = $user->getUserEmail();
            }
        }

        if (!$owner) {
            $this->addStatusMessage(
                sprintf(_('VoIP Number %s does not belong to you'), $number),
                'warning',
            );

            return false;
        }

        return true;
    }

    /**
     * Resolve AbraFlexi customer name from IPEX customer name (legacy LMS fallback).
     */
    public function ipexCustomerNameToAbraFlexiCustomer(string $ipexAddressName): string
    {
        $abraFlexiName = 'n/a';
        $adresar = \Ease\Shared::user()->getAdresar();

        if (preg_match('/(.*)\s\((.*?)\)/i', $ipexAddressName, $match)) {
            $adresInfoRaw = $adresar->getColumnsFromAbraFlexi(
                'nazev',
                ['id' => 'ext:lms.cstmr:'.(int) $match[2]],
            );
            if (!empty($adresInfoRaw[0]['nazev'])) {
                $abraFlexiName = $adresInfoRaw[0]['nazev'];
            }
        } else {
            $adresInfoRaw = $adresar->getColumnsFromAbraFlexi(
                'nazev',
                ['nazev' => trim($ipexAddressName)],
            );
            if (is_array($adresInfoRaw) && count($adresInfoRaw) === 1) {
                $abraFlexiName = $adresInfoRaw[0]['nazev'];
            }
        }

        return $abraFlexiName;
    }

    /**
     * @param \ClientZone\OrderItem $order
     *
     * @return array<string, mixed>
     */
    public function processFields($order): array
    {
        $coeficient = 21 / 121;
        $price = (float) $order->getDataValue('credit');
        $dph = $coeficient * $price;

        $orderItemData = parent::processFields($order);
        $orderItemData['cenaZaklBezDph'] = $price - $dph;
        $orderItemData['cenaMj'] = $price;
        $orderItemData['cenaZaklVcDph'] = $price;
        $orderItemData['mnozMj'] = 1;
        $orderItemData['poznam'] = $order->getDataValue('number');
        $orderItemData['ipexuser'] = (string) $this->ipexUserID;
        $orderItemData['phoneno'] = ltrim((string) $order->getDataValue('number'), '+');
        $orderItemData['notify'] = $this->customerEmail;
        $orderItemData['nazev'] = _('VoIP Credit').': '.$price;
        $orderItemData['typCenyDphK'] = 'typCeny.sDph';
        $orderItemData['typSzbDphK'] = 'typSzbDph.dphZakl';
        $orderItemData['typZasobyK'] = 'typZasoby.poplatek';
        $orderItemData['stitky'] = 'API';

        return $orderItemData;
    }

    /**
     * Fulfilment is done by abraflexi-voip-credit after payment.
     */
    public function settled()
    {
        $this->addStatusMessage(
            sprintf(_('Item %s will be settled by abraflexi-voip-credit after payment'), $this->productCode),
            'info',
        );

        return true;
    }
}
