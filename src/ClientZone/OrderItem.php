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
class OrderItem extends \AbraFlexi\RW
{
    /**
     * Customer
     * @var Customer
     */
    public $customer;

    /**
     * Pricelist
     * @var \AbraFlexi\Cenik
     */
    public $cenik = null;

    /**
     * Currency Amount
     * @var float
     */
    public $credit = null;

    /**
     * Service FlexiBee Code
     * @var string
     */
    public $service = null;

    /**
     * Domain Name or Phone Number
     * @var string
     */
    public $detail = null;

    /**
     * Array of Form Plugins
     * @var array
     */
    public $plugins = [];

    /**
     * Order Class
     * @param array $formdata
     */
    public function __construct($formdata = array())
    {
        parent::__construct();
        $this->takeData($formdata);
        $this->customer = new Customer();
        $this->cenik    = new \AbraFlexi\Cenik();
        $this->loadPlugins();
    }

    /**
     * Data to use in object
     *
     * @param array $data
     * @return int
     */
    public function takeData($data)
    {
        if (isset($data['lmsid'])) {
            $data['lmsid'] = intval($data['lmsid']);
        }
        return parent::takeData($data);
    }

    /**
     * Load Order Plugins
     *
     * @return int Loaded plugins count
     */
    public function loadPlugins()
    {
        $d     = dir("ClientZone/orderplugins");
        while (false !== ($entry = $d->read())) {
            if ($entry[0] == '.') {
                continue;
            }
            $pluginName = str_replace('.php', '', $entry);
            $className  = '\\ClientZone\\orderplugins\\'.$pluginName;

            /**
             * @var \ClientZone\OrderPlugin OrderPlugin
             */
            $this->plugins[$pluginName]             = new $className;
            $this->plugins[$pluginName]->easeShared = $this->easeShared;
        }
        $d->close();
        return count($this->plugins);
    }

    /**
     * Check order input
     *
     * @return boolean
     */
    public function checkInput()
    {
        return $this->checkService($this->getDataValue('service')) && $this->checkByPlugin();
    }

    /**
     * Check if service/product is choosen
     * 
     * @param string $service
     * @return boolean
     */
    public function checkService($service)
    {
        $ok = false;
        if (!strlen($service)) {
            $this->addStatusMessage(_('Please choose service'), 'info');
        } else {
            if ($service == 'Common') {
                $code = $this->getDataValue('kod');
            } else {
                $code = urlencode($this->plugins[$service]->productCode);
            }

            if (strlen(trim($code))) {
                $this->cenik->loadFromAbraFlexi('code:'.$code);
                if ($this->cenik->lastResponseCode == 200) {
//                $this->addStatusMessage($this->cenik->getDataValue('nazev'),
//                    'success');
                    $ok = true;
                } else {
                    $this->addStatusMessage(sprintf(_('Product with code %s does not exist'),
                            $code), 'warning');
                    $ok = false;
                }
            } else {
                $this->addStatusMessage(sprintf(_('Missing product code'), $code),
                    'warning');
                $ok = false;
            }
        }
        return $ok;
    }

    /**
     * Check requirements for form choosen Plugin\
     *
     * @return boolean
     */
    public function checkByPlugin()
    {
        return $this->getServicePlugin()->controlFields($this);
    }

    /**
     * Obtain Curent Service plugin object
     *
     * @return OrderPlugin
     */
    public function getServicePlugin()
    {
        $servicePlugin = null;
        $service       = $this->getDataValue('service');
        if (isset($this->plugins[$service]) && is_object($this->plugins[$service])) {
            $servicePlugin = $this->plugins[$service];
        }
        return $servicePlugin;
    }

    /**
     * Make proforma for customer
     *
     * @param float $credit
     * @param string $service
     *
     * @return \AbraFlexi\FakturaVydana
     */
    public function makeProforma()
    {
        $polozka = $this->getServicePlugin()->processFields($this);

        $proforma = new \AbraFlexi\FakturaVydana();

        $today = date('Y-m-d');
        $proforma->setDataValue('firma', $this->customer->adresar);
        $proforma->setDataValue('typDokl', 'code:ZALOHA');
        $proforma->setDataValue('uvodTxt',
            $this->cenik->getDataValue('nazev').' '.$polozka['poznam']);
        $proforma->setDataValue('zavTxt', '');
        $proforma->setDataValue('poznam', 'WebForm');
        $proforma->setDataValue('duzpPuv', $today);
        $proforma->setDataValue('duzpUcto', $today);
        $proforma->setDataValue('datUcto', $today);
        $proforma->setDataValue('stitky', ['SYSTEM', 'API']);
        $proforma->addArrayToBranch($polozka, 'polozkyFaktury');
        //$proforma->debug = true;
        $proforma->insertToAbraFlexi();

        if (\AbraFlexi\Priloha::addAttachment($proforma, 'order.json',
                json_encode(array_merge($polozka,
                        ['service' => $this->getDataValue('service')])),
                'application/json') != 201) {
            $proforma->addStatusMessage('Saving Order Details '.$proforma->getAbraFlexiURL().' Failed',
                'error');
        }

        return $proforma;
    }

    /**
     * Add Item to cart
     * 
     * @param int $itemId
     */
    public function addToCart($itemId = null)
    {
        $polozka            = $this->getServicePlugin()->processFields($this);
        $polozka['service'] = $this->getDataValue('service');
        if (array_key_exists('cenik', $polozka)) {
            $this->cenik->loadFromAbraFlexi($polozka['cenik']);
            $polozka['icon']       = $this->cenik->getDataValue('id');
            $nazev                 = $this->cenik->getDataValue('nazev');
            $polozka['typZasobyK'] = $this->cenik->getDataValue('typZasobyK');

            if (empty($polozka['cenaMj'])) {
                if (!array_key_exists('cenaZaklBezDph', $polozka)) {
                    $polozka['cenaZaklBezDph'] = $this->cenik->getDataValue('cenaZaklBezDph');
                }
                if (!array_key_exists('cenaZaklVcDph', $polozka)) {
                    $polozka['cenaZaklVcDph'] = $this->cenik->getDataValue('cenaZaklVcDph');
                }
            } else {
                $polozka['cenaZaklBezDph'] = $polozka['cenaMj'];
                $polozka['cenaZaklVcDph']  = $polozka['cenaMj'] + ($polozka['cenaMj']
                    * 0.21);
            }
        } else {
            $nazev = $polozka['nazev'];
        }

        if (is_null($itemId)) {
            $_SESSION['cart'][] = $polozka;
            $this->addStatusMessage(sprintf(_('%s added to cart'), $nazev),
                'success');
        } else {
            $_SESSION['cart'][$itemId] = $polozka;
            $this->addStatusMessage(sprintf(_('%s chnged in cart'), $nazev),
                'success');
        }
    }
}
