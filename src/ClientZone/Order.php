<?php
/**
 * clientzone - Objednavka.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017 VitexSoftware v.s.cz
 */

namespace ClientZone;

/**
 * Description of Order
 *
 * @author vitex
 */
class Order extends \AbraFlexi\RW
{
    /**
     * Customer
     * @var Customer
     */
    public $customer;

    /**
     * Výchozí evidence objednavky.
     */
    public ?string $evidence = 'faktura-vydana';

    /**
     * CartData
     * @var array
     */
    public $cartData = [];

    /**
     * Order Class
     * @param array $formdata
     */
    public function __construct($cartData = [])
    {
        $this->cartData = $cartData;
        $this->customer = \Ease\Shared::user();
        parent::__construct();
    }

    /**
     * Make Order for customer
     *
     * @param float $credit
     * @param string $service
     *
     * @return \AbraFlexi\RW
     */
    public function makeOrder($items = null)
    {
        if (is_null($items)) {
            $items = $this->cartData;
        }

        $objednavka = new \AbraFlexi\RW(null,
            ['evidence' => 'objednavka-prijata']);

        $today = date('Y-m-d');
        $objednavka->setDataValue('firma', $this->customer->getAdresar());
        $objednavka->setDataValue('typDokl', 'code:OBP'); //Objednávka přijatá
        $objednavka->setDataValue('uvodTxt',
            _('Please wait while our salesman contact you.'));

        $objednavka->setDataValue('zavTxt', '');
        $objednavka->setDataValue('poznam', _('purchase order'));
        $objednavka->setDataValue('typDoklFak', \AbraFlexi\Functions::code(
            \Ease\Shared::cfg('ABRAFLEXI_PROFORMA_DOCTYPE', 'ZALOHA')));
        $objednavka->setDataValue('duzpPuv', $today);
        $objednavka->setDataValue('duzpUcto', $today);
        $objednavka->setDataValue('datUcto', $today);
//        $objednavka->setDataValue('stitky', ['SYSTEM', 'API']);

        foreach ($items as $polozka) {
            $polozka['cenik'] = urldecode($polozka['cenik']);
            $objednavka->addArrayToBranch($polozka, 'polozkyObchDokladu');
        }
        //$proforma->debug = true;
        $objednavka->insertToAbraFlexi();
        $objednavka->loadFromAbraFlexi();
        $this->attachOrderJson($objednavka, $items);

        return $objednavka;
    }

    /**
     * Make proforma for customer
     *
     * @param float $credit
     * @param string $service
     *
     * @return \AbraFlexi\FakturaVydana
     */
    public function makeProforma($items = null)
    {
        if (is_null($items)) {
            $items = $this->cartData;
        }

        $proforma = new \AbraFlexi\FakturaVydana();

        $today = date('Y-m-d');
        $proforma->setDataValue('firma', $this->customer->getAdresar());
        $proforma->setDataValue('typDokl', \AbraFlexi\Functions::code(
            \Ease\Shared::cfg('ABRAFLEXI_PROFORMA_DOCTYPE', 'ZALOHA')));
        $proforma->setDataValue('uvodTxt', \Ease\Shared::appName());
        // typDoklSkl only when configured — flexibee-dev has no code:STANDARD
        $typDoklSkl = \Ease\Shared::cfg('ABRAFLEXI_STOCK_DOCTYPE', '');
        if ($typDoklSkl !== null && $typDoklSkl !== '') {
            $proforma->setDataValue('typDoklSkl', \AbraFlexi\Functions::code($typDoklSkl));
        }

        $proforma->setDataValue('zavTxt', '');
        $proforma->setDataValue('poznam', _('Proforma'));
        $proforma->setDataValue('duzpPuv', $today);
        $proforma->setDataValue('duzpUcto', $today);
        $proforma->setDataValue('datUcto', $today);
        // API label = pending fulfilment for abraflexi-voip-credit / abraflexi-domain-credit
        $proforma->setDataValue('stitky', ['SYSTEM', 'API']);

        foreach ($items as $polozka) {
            $proforma->addArrayToBranch($polozka, 'polozkyFaktury');
        }
        //$proforma->debug = true;
        if ($proforma->insertToAbraFlexi()) {
            $proforma->loadFromAbraFlexi();
            $this->attachOrderJson($proforma, $items);
        }

        return $proforma;
    }

    /**
     * Make Invoice for customer
     *
     * @param float $credit
     * @param string $service
     *
     * @return \AbraFlexi\FakturaVydana
     */
    public function makeInvoice($items = null)
    {
        if (is_null($items)) {
            $items = $this->cartData;
        }

        $invoice = new \AbraFlexi\FakturaVydana();

        $today = date('Y-m-d');
        $invoice->setDataValue('firma', $this->customer->getAdresar());
        $invoice->setDataValue('typDokl', 'code:FAKTURA');
        $invoice->setDataValue('uvodTxt', '');

        $invoice->setDataValue('zavTxt', '');
        $invoice->setDataValue('poznam', _('Invoice'));
        $invoice->setDataValue('duzpPuv', $today);
        $invoice->setDataValue('duzpUcto', $today);
        $invoice->setDataValue('datUcto', $today);
        $invoice->setDataValue('stitky', ['SYSTEM', 'API']);

        foreach ($items as $polozka) {
            $invoice->addArrayToBranch($polozka, 'polozkyFaktury');
        }
        //$proforma->debug = true;
        $invoice->insertToAbraFlexi();
        $invoice->loadFromAbraFlexi();
        $this->attachOrderJson($invoice, $items);

        return $invoice;
    }

    /**
     * Attach cart payload as order.json (Priloha::addAttachment returns Priloha, not HTTP code).
     *
     * @param array<int|string, mixed> $items
     */
    private function attachOrderJson(\AbraFlexi\RW $document, array $items): void
    {
        $attachment = \AbraFlexi\Priloha::addAttachment(
            $document,
            'order.json',
            json_encode($items),
            'application/json',
        );
        if ($attachment->lastResponseCode !== 201) {
            $document->addStatusMessage(
                sprintf(_('Saving Order Details %s Failed'), $document->getAbraFlexiURL()),
                'error',
            );
        }
    }

    public function finishOrder()
    {
        $invoiceTypeItems  = [];
        $proformaTypeItems = [];
        $orderTypeItems    = [];

        $result = [];

        $items = $this->cartData;
        foreach ($items as $itemId => $item) {
            switch ($item['typZasobyK']) {
                case 'typZasoby.sluzba':
                    $orderTypeItems[$itemId] = $item;
                    break;

                case 'typZasoby.zbozi':
                case 'typZasoby.material':
                case 'typZasoby.poplatek':
                    $proformaTypeItems[$itemId] = $item;
                    break;

                default:
                    $this->addStatusMessage(sprintf(_('Unhandled type of item'),
                            $item['typZasobyK']), 'error');
                    //typZasoby.nedvyroba
                    //typZasoby.polotovar
                    //typZasoby.vyrobek
                    //typZasoby.zvire
                    break;
            }
        }

        if (count($invoiceTypeItems)) {
            $result['invoice'] = $this->makeInvoice($invoiceTypeItems);
        }
        if (count($proformaTypeItems)) {
            $result['proforma'] = $this->makeProforma($proformaTypeItems);
        }
        if (count($orderTypeItems)) {
            $result['order'] = $this->makeOrder($orderTypeItems);
        }

        return $result;
    }
}
