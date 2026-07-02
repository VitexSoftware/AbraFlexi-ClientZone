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
     * Výchozí evidence objednavky
     * @var string
     */
    public $evidence = 'faktura-vydana';

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
        $objednavka->setDataValue('firma', $this->customer->adresar);
        $objednavka->setDataValue('typDokl', 'code:OBP'); //Objednávka přijatá
        $objednavka->setDataValue('uvodTxt',
            _('Please wait while our salesman contact you.'));

        $objednavka->setDataValue('zavTxt', '');
        $objednavka->setDataValue('poznam', _('purchase order'));
        $objednavka->setDataValue('typDoklFak', 'code:ZALOHA');
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
        if (\AbraFlexi\Priloha::addAttachment($objednavka, 'order.json',
                json_encode($items), 'application/json') != 201) {
            $objednavka->addStatusMessage('Saving Order Details '.$objednavka->getAbraFlexiURL().' Failed',
                'error');
        }
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
        $proforma->setDataValue('firma', $this->customer->adresar);
        $proforma->setDataValue('typDokl', 'code:ZÁLOHA');
        $proforma->setDataValue('uvodTxt', \Ease\Shared::appName());
        $proforma->setDataValue('typDoklSkl', 'code:STANDARD');


        $proforma->setDataValue('zavTxt', '');
        $proforma->setDataValue('poznam', _('Proforma'));
        $proforma->setDataValue('duzpPuv', $today);
        $proforma->setDataValue('duzpUcto', $today);
        $proforma->setDataValue('datUcto', $today);
//        $proforma->setDataValue('stitky', ['SYSTEM', 'API']);

        foreach ($items as $polozka) {
            $proforma->addArrayToBranch($polozka, 'polozkyFaktury');
        }
        //$proforma->debug = true;
        if ($proforma->insertToAbraFlexi()) {
            $proforma->loadFromAbraFlexi();
            if (\AbraFlexi\Priloha::addAttachment($proforma, 'order.json',
                    json_encode($items), 'application/json') != 201) {
                $proforma->addStatusMessage('Saving Order Details '.$proforma->getAbraFlexiURL().' Failed',
                    'error');
            }
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
        $invoice->setDataValue('firma', $this->customer->adresar);
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
        if (\AbraFlexi\Priloha::addAttachment($invoice, 'order.json',
                json_encode($items), 'application/json') != 201) {
            $invoice->addStatusMessage('Saving Order Details '.$invoice->getAbraFlexiURL().' Failed',
                'error');
        }
        return $invoice;
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
