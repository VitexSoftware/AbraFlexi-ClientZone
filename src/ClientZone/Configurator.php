<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ClientZone;

/**
 * Description of Configurator
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class Configurator extends \Ease\Brick
{
    /**
     *
     * @var string 
     */
    public $settingsFile = 'settings.json';

    /**
     * Saves obejct instace (singleton...).
     *
     * @var Shared
     */
    private static $_instance = null;

    /**
     *
     * @var array 
     */
    private $myKeywords = [
        "SEND_MAILS_FROM" => 'string',
        "SEND_INFO_TO" => 'string',
        "EMAIL_FROM" => 'string',
        "EASE_EMAILTO" => 'string',
        "SUPPRESS_EMAILS" => 'boolean',
        "ALLOW_REGISTER" => 'boolean',
        "SHOW_PRICELIST" => 'boolean',
        "PROD_CAT" => 'string',
        "DEBUG" => 'boolean',
        "CONFIGURED" => 'boolean',
        "EASE_LOGGER" => 'string',
        "ABRAFLEXI_URL" => 'string',
        "ABRAFLEXI_LOGIN" => 'string',
        "ABRAFLEXI_PASSWORD" => 'string',
        "ABRAFLEXI_COMPANY" => 'string'
    ];

    /**
     * 
     * @param string $settingsFile
     * @param array $intialData
     */
    public function __construct($settingsFile = '../settings.json',
                                $intialData = [])
    {
        parent::__construct();
        $this->settingsFile = $settingsFile;
        $this->setDefaults($intialData);
        $this->loadData();
    }

    /**
     * Set Initial defaults 
     * 
     * @param array $intialData
     */
    public function setDefaults($intialData = [])
    {
        foreach ($this->myKeywords as $keyWord => $type) {
            if (array_key_exists($keyWord, $intialData)) {
                $this->setDataValue($keyWord, $intialData[$keyWord]);
            } else {
                switch ($type) {
                    case 'boolean':
                        $this->setDataValue($keyWord, false);
                        break;
                    default:
                        $this->setDataValue($keyWord, '');
                        break;
                }
            }
        }
    }

    /**
     * Publish Options as constants
     */
    public function publish()
    {
        $shared = \Ease\Shared::instanced();
        foreach ($this->getData() as $configKey => $configValue) {
            $shared->setConfigValue($configKey, $configValue);
            if ((strtoupper($configKey) == $configKey) && (!defined($configKey))) {
                define($configKey, $configValue);
            }
        }
    }

    public function takeData($data): int
    {
        if ($data) {
            foreach ($data as $key => $value) {
                if (!array_key_exists($key, $this->myKeywords)) {
                    unset($data[$key]);
                } else {
                    switch ($this->myKeywords[$key]) {
                        case 'boolean':
                            $data[$key] = boolval($value);

                            break;

                        default:
                            break;
                    }
                }
            }
            return parent::takeData($data);
        } else {
            return 0;
        }
    }

    /**
     * Add Missing boolean falses
     * 
     * @param array $dataRaw
     * 
     * @return array
     */
    public function processForm($data)
    {
        foreach ($this->myKeywords as $keyWord => $keyType) {
            switch ($keyType) {
                case 'boolean':
                    if (array_key_exists($keyWord, $data)) {
                        $data[$keyWord] = true;
                    } else {
                        $data[$keyWord] = false;
                    }
                    break;

                default:
                    break;
            }
        }
        return $data;
    }

    /**
     * Pri vytvareni objektu pomoci funkce singleton (ma stejne parametry, jako konstruktor)
     * se bude v ramci behu programu pouzivat pouze jedna jeho Instance (ta prvni).
     *
     * @param string $class název třídy jenž má být zinstancována
     *
     * @link   http://docs.php.net/en/language.oop5.patterns.html Dokumentace a priklad
     *
     * @return \Ease\Shared
     */
    public static function singleton($class = null)
    {
        if (!isset(self::$_instance)) {
            if (is_null($class)) {
                $class = __CLASS__;
            }
            self::$_instance = new $class();
        }

        return self::$_instance;
    }

    /**
     * 
     * @return int
     */
    public function loadData()
    {
        if (file_exists($this->settingsFile)) {
            return $this->takeData(json_decode(file_get_contents($this->settingsFile),
                        true));
        }
    }

    /**
     * 
     * @return string
     */
    public function getJson($data = [])
    {
        if (empty($data)) {
            $data = $this->getData();
        }
        return json_encode($data, JSON_PRETTY_PRINT);
    }

    /**
     * Serialise data as KEY=value .env lines
     */
    public function getEnv(array $data = []): string
    {
        if (empty($data)) {
            $data = $this->getData();
        }
        $lines = [];
        foreach ($data as $key => $value) {
            $escaped = str_replace('"', '\\"', (string) $value);
            $lines[] = $key.'="'.$escaped.'"';
        }
        return implode("\n", $lines)."\n";
    }

    /**
     * Save Configuration to file
     *
     * @return boolean
     */
    public function saveDataFile(array $data, string $configFile): bool
    {
        $content = str_ends_with($configFile, '.env')
            ? $this->getEnv($data)
            : $this->getJson($data);
        if (file_put_contents($configFile, $content)) {
            $this->addStatusMessage(sprintf(_('Configuration file %s was saved'),
                    $configFile), 'success');
            return true;
        }
        $this->addStatusMessage(sprintf(_('Configuration file %s was not saved'),
                $configFile), 'error');
        return false;
    }

    public function getAppData()
    {
        return array_filter($this->getData(),
            function($k) {
            return !strstr($k, 'ABRAFLEXI_');
        }, ARRAY_FILTER_USE_KEY);
    }

    public function getAbraFlexiData()
    {
        return array_filter($this->getData(),
            function($k) {
            return strstr($k, 'ABRAFLEXI_');
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Save all configuration to .env file
     *
     * @return boolean
     */
    public function saveAppConfig(): bool
    {
        return $this->saveConfig();
    }

    /**
     * Save FlexiBee Configuration to .env file
     *
     * @return boolean
     */
    public function saveAbraFlexiConfig(): bool
    {
        return $this->saveConfig();
    }

    /**
     * Save all configuration to .env file
     *
     * @return boolean
     */
    public function saveConfig(): bool
    {
        $envFile = file_exists('/etc/abraflexi/clientzone.env')
            ? '/etc/abraflexi/clientzone.env'
            : \dirname(__DIR__, 3).'/.env';
        return $this->saveDataFile($this->getData(), $envFile);
    }
}
