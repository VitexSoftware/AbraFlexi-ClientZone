<?php
/**
 * ClientZone - databázový objekt.
 *
 * @author     Vítězslav Dvořák <vitex@murka.cz>
 * @copyright  2017 VitexSoftware v.s.cz
 */

namespace ClientZone;

class Engine extends \Ease\Brick
{
    /**
     * We are log to Syslog.
     */
    public $logType = 'syslog';

    /**
     * obsah všech záznamu tabulky.
     *
     * @var array
     */
    public $listingData = null;

    /**
     * Jméno záznamu.
     *
     * @var string
     */
    public $nameColumn = 'name';

    /**
     * Klíčové slovo třídy.
     *
     * @var type
     */
    public $keyword = null;

    /**
     * Popis datových políček.
     *
     * @var array
     */
    public $useKeywords = [];

    /**
     * Doplňující informace.
     *
     * @var array
     */
    public $keywordsInfo = [];

    /**
     * Základní objekt DBFinance.
     *
     * @param int|string $itemID
     */
    public function __construct($itemID = null)
    {
        parent::__construct();

        if (!is_null($itemID)) {
            if (is_string($itemID) && $this->nameColumn) {
                $this->setKeyColumn($this->nameColumn);
                $this->loadFromSQL($itemID);
                $this->resetObjectIdentity();
            } else {
                $this->loadFromSQL($itemID);
            }
        }
        if (is_null($this->keyword)) {
            $this->keyword = $this->myTable;
        }
    }

    /**
     * Převezme data do objektu.
     *
     * @param array  $data
     * @param string $dataPrefix
     *
     * @return int
     */
    public function takeData($data, $dataPrefix = null)
    {
        unset($data['class']);
        foreach ($data as $colName => $value) {
            if (!isset($this->useKeywords[$colName])) {
                unset($data[$colName]);
            }
        }

        foreach ($this->useKeywords as $column => $type) {
            $type = preg_replace('/[^A-Z]+/', '', $type);
            if (!isset($data[$column]) && (strtolower($type) != 'bool')) {
                continue;
            }
            switch (strtolower($type)) {
                case 'bool':
                    if (!isset($data[$column])) {
                        $data[$column] = false;
                    } else {
                        $data[$column] = boolval($data[$column]);
                    }

                    break;
                case 'date':
                    if (strstr($data[$column], '.')) {
                        $data[$column] = DateTime::createFromFormat('d.m.Y',
                                $data[$column])->format('Y-m-d');
                    } else {
                        if (!strstr($data[$column], '-')) {
                            $data[$column] = null;
                        }
                    }
                    break;
                case 'datetime':
                    $date          = DateTime::createFromFormat('m-d-Y',
                            $enquiry_time);
                    $data[$column] = $date->format('Y-m-d');
                    break;
                case 'text':
                case 'string':
                case 'varchar':
                    $data[$column] = addSlashes($data[$column]);
                    break;
                case 'int':
                    if (isset($data[$column])) {
                        $data[$column] = intval($data[$column]);
                    }
                    break;
                case 'float':
                    if (isset($data[$column])) {
                        $data[$column] = floatval($data[$column]);
                    }
                    break;
                default:
                    // $this->addStatusMessage(_('Neznámý druh dat') . ': ' . $type, 'warning');
                    break;
            }
        }

        return parent::takeData($data, $dataPrefix);
    }

    /**
     * Zpracuje odeslaný formulář.
     *
     * @param ABWebPage $oPage
     *
     * @return \class
     */
    public static function &doThings($oPage)
    {
        $engine = null;
        $class  = $oPage->getRequestValue('class');
        if ($class) {
            $engine = new $class();
            $key    = $oPage->getRequestValue($engine->keyColumn);
            if ($key) {
                $engine->setMyKey((int) $key);
            }

            if ($oPage->isPosted()) {
                if (isset($_POST['gotolist'])) {
                    unset($_POST['gotolist']);
                    $gotolist = true;
                } else {
                    $gotolist = false;
                }
                $engine->takeData($_POST);
                if (isset($engine->evidence)) {
                    $saveResult = $engine->insertToAbraFlexi();
                } else {
                    $saveResult = $engine->saveToSQL();
                }
                if (!is_null($saveResult) && ($saveResult !== false)) {
                    $engine->addStatusMessage(_('Záznam byl uložen'), 'success');
//                    $engine->handleUpload();
                    if ($gotolist) {
                        $oPage->redirect($engine->keyword.'s.php');
                        exit();
                    }
                } else {
                    $engine->addStatusMessage(_('Záznam nebyl uložen'),
                        'warning');
                }
            } else {
                $engine->loadFromSQL();
            }
        }

        return $engine;
    }

    /**
     * Vrací data jako HTML.
     *
     * @param array $data
     *
     * @return array
     */
    public function htmlizeData($data)
    {
        if (is_array($data) && count($data)) {
            $usedCache = [];
            foreach ($data as $rowId => $row) {
                $htmlized = $this->htmlizeRow($row);

                if (is_array($htmlized)) {
                    foreach ($htmlized as $key => $value) {
                        if (!is_null($value)) {
                            $data[$rowId][$key] = $value;
                        } else {
                            if (!isset($data[$rowId][$key])) {
                                $data[$rowId][$key] = $value;
                            }
                        }
                    }
                    if (isset($row['register']) && ($row['register'] == 1)) {
                        $data[$rowId]['name'] = '';
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Připaví data na export jak CSV.
     *
     * @param array $data
     *
     * @return array
     */
    public function csvizeData($data)
    {
        if (is_array($data) && count($data)) {
            foreach ($data as $rowId => $row) {
                $data[$rowId] = $this->csvizeRow($row);
            }
        }

        return $data;
    }

    /**
     * Připraví rata pro export do CSV.
     *
     * @param array $row
     */
    public function csvizeRow($row)
    {
        foreach ($row as $column => $value) {
            if (!is_array($value) && strstr($value, ':{')) {
                $value = unserialize($value);
            }
            if (is_array($value)) {
                $row[$column] = implode('|',
                    (array) new \RecursiveIteratorIterator(new \RecursiveArrayIterator($value)));
            }
        }

        return $row;
    }

    /**
     * Vrací řádek dat jako html.
     *
     * @param array $row
     *
     * @return array
     */
    public function htmlizeRow($row)
    {
        if (!isset($row['color'])) {
            $row['color'] = null;
        }

        if (is_array($row) && count($row)) {
            foreach ($row as $key => $value) {
                if ($key == $this->keyColumn) {
                    continue;
                }
                if (!isset($this->useKeywords[$key])) {
                    continue;
                }
                $fieldType = $this->useKeywords[$key];
                $fType     = preg_replace('/\(.*\)/', '', $fieldType);
                switch ($fType) {
                    case 'BOOL':
                        if (is_null($value) || !strlen($value)) {
                            $row[$key] = '<em>NULL</em>';
                        } else {
                            if ($value === '0') {
                                $row[$key] = '<i class="fa fa-square-o"></i>';
                            } else {
                                if ($value === '1') {
                                    $row[$key] = '<i class="fa fa-check"></i>';
                                }
                            }
                        }
                        break;
                    case 'SELECT':
                        if (strlen($value)) {
                            if (isset($this->keywordsInfo[$key]['refdata'])) {
                                $idcolumn   = $this->keywordsInfo[$key]['refdata']['idcolumn'];
                                $nameColumn = $this->keywordsInfo[$key]['refdata']['captioncolumn'];
                                $table      = $this->keywordsInfo[$key]['refdata']['table'];
                                $found      = $this->dblink->queryToValue('SELECT `'.$nameColumn.'` FROM '.$table.' WHERE '.$idcolumn.'= '.$value);
                                if ($found) {
                                    $row[$key] = '<a title="'.$table.'" href="'.$table.'.php?'.$idcolumn.'='.$value.'">'.$found.'</a> ';
                                } else {
                                    $row[$key] = '<em style="color: red" title="'._('Chybná hodnota').'">'.$row[$key].'</em>';
                                }
                            }
                        }
                        break;
                    case 'IDLIST':
                        if (strlen($value)) {
                            $values = unserialize($value);
                            if (isset($this->keywordsInfo[$key]['refdata'])) {
                                $idcolumn     = $this->keywordsInfo[$key]['refdata']['idcolumn'];
                                $table        = $this->keywordsInfo[$key]['refdata']['table'];
                                $searchColumn = $this->keywordsInfo[$key]['refdata']['captioncolumn'];
                                $target       = str_replace('_id', '.php',
                                    $idcolumn);
                                foreach ($values as $id => $name) {
                                    if ($id) {
                                        $values[$id] = '<a title="'.$table.'" href="'.$target.'?'.$idcolumn.'='.$id.'">'.$name.'</a>';
                                    } else {
                                        $values[$id] = '<a title="'.$table.'" href="search.php?search='.$name.'&table='.$table.'&column='.$searchColumn.'">'.$name.'</a> '.'<i class="fa fa-search"></i>';
                                    }
                                }
                            }
                            $value     = implode(',', $values);
                            $row[$key] = $value;
                        }
                        break;
                    default :
                        if (isset($this->keywordsInfo[$key]['refdata']) && strlen(trim($value))) {
                            $table        = $this->keywordsInfo[$key]['refdata']['table'];
                            $searchColumn = $this->keywordsInfo[$key]['refdata']['captioncolumn'];
                            $row[$key]    = '<a title="'.$table.'" href="search.php?search='.$value.'&table='.$table.'&column='.$searchColumn.'">'.$value.'</a> '.'<i class="fa fa-search"></i>';
                        }
                        if (strstr($key, 'image') && strlen(trim($value))) {
                            $row[$key] = '<img title="'.$value.'" src="logos/'.$value.'" class="gridimg">';
                        }
                        if (strstr($key, 'url')) {
                            $row[$key] = '<a href="'.$value.'">'.$value.'</a>';
                        }

                        break;
                }
            }
        }

        return $row;
    }

    /**
     * Vrací ID aktuálního záznamu.
     *
     * @return int
     */
    public function getId()
    {
        $id = $this->getMyKey();
        if (is_null($id)) {
            return;
        }

        return (int) $id;
    }

    /**
     * Nastaví ID aktuálního záznamu.
     *
     * @param int $id
     *
     * @return bool
     */
    public function setId($id)
    {
        return $this->setMyKey($id);
    }

    /**
     * Vrací jméno aktuální položky.
     *
     * @return string
     */
    public function getName($data = null)
    {
        if (is_null($data)) {
            return $this->getDataValue($this->nameColumn);
        } else {
            return $data[$this->nameColumn];
        }
    }

    /**
     * Smaže záznam.
     *
     * @param int $id má li být smazán jiný než aktuální záznam
     *
     * @return bool smazal se záznam ?
     */
    public function delete($id = null)
    {
        if (is_null($id)) {
            $id = $this->getId();
        }

        if (isset($this->data)) {
            foreach ($this->data as $columnName => $value) {
                if (is_array($value)) {
                    $this->unsetDataValue($columnName);
                }
            }
        }
        if ($this->deleteFromSQL($id)) {
            $this->addStatusMessage(sprintf(_(' %s %s byl smazán '),
                    $this->keyword, $this->getName()), 'success');
            $this->dataReset();

            return true;
        } else {
            $this->addStatusMessage(sprintf(_(' %s %s nebyl smazán '),
                    $this->keyword, $this->getName()), 'warning');

            return false;
        }
    }

    /**
     * Vrací fragment SQL.
     *
     * @return string
     */
    public function getListingQuerySelect()
    {
        return 'SELECT * FROM `'.$this->getMyTable().'`';
    }

    /**
     * Vrací fragment za WHERE pro složitější sql dotazy.
     *
     * @return string
     */
    public function getListingQueryWhere()
    {
        return;
    }

    /**
     * Místní nabídka objektu.
     *
     * @return \Ease\TWB5\ButtonGroup
     */
    public function operationsMenu()
    {
        $id = $this->getId();
        $dropdown = new \Ease\Html\DivTag(null, ['class' => 'btn-group', 'role' => 'group']);
        $toggleBtn = $dropdown->addItem(new \Ease\Html\ButtonTag(
            '<i class="fa fa-cog"></i>',
            ['class' => 'btn btn-warning dropdown-toggle', 'data-bs-toggle' => 'dropdown', 'aria-expanded' => 'false']
        ));
        $menu = $dropdown->addItem(new \Ease\Html\UlTag(null, ['class' => 'dropdown-menu']));
        $menu->addItem(new \Ease\Html\LiTag(new \Ease\Html\ATag(
            $this->keyword.'.php?action=delete&'.$this->keyColumn.'='.$id,
            '<i class="fa fa-times"></i>'.' '._('Smazat'),
            ['class' => 'dropdown-item']
        )));
        $menu->addItem(new \Ease\Html\LiTag(new \Ease\Html\ATag(
            $this->keyword.'.php?'.$this->keyColumn.'='.$id,
            '<i class="fa fa-edit"></i>'.' '._('Modify'),
            ['class' => 'dropdown-item']
        )));
        return $dropdown;
    }

    /**
     * Nic.
     */
    public function handleUpload()
    {
        return;
    }

    /**
     * Vyhledavani v záznamech objektu.
     *
     * @param string $what hledaný výraz
     *
     * @return array pole výsledků
     */
    public function searchString($what)
    {
        $results   = [];
        $conds     = [];
        $columns[] = $this->keyColumn;
        foreach ($this->useKeywords as $keyword => $keywordInfo) {
            if (isset($this->keywordsInfo[$keyword]['virtual']) && ($this->keywordsInfo[$keyword]['virtual']
                == true)) {
                if ($keyword == $this->nameColumn) {
                    $this->nameColumn = $this->keyColumn;
                }
                continue;
            }
            switch ($keywordInfo) {
                case 'TEXT':
                case 'STRING':
                    $conds[]   = " `$keyword` LIKE '%".$what."%'";
                    $columns[] = "`$keyword`";
                    break;

                default:
                    if (strstr($keywordInfo, 'VARCHAR')) {
                        $conds[]   = " `$keyword` LIKE '%".$what."%'";
                        $columns[] = "`$keyword`";
                    }
                    break;
            }
        }

        $res = \Ease\Shared::db()->queryToArray('SELECT '.implode(',', $columns).','.$this->nameColumn.' FROM '.$this->myTable.' WHERE '.implode(' OR ',
                $conds).' ORDER BY '.$this->nameColumn, $this->keyColumn);
        foreach ($res as $result) {
            $occurences = '';
            foreach ($result as $key => $value) {
                if (mb_stristr($value, $what)) {
                    $occurences .= '('.$key.': '.$value.')';
                }
            }
            $results[$result[$this->keyColumn]] = [$this->nameColumn => $result[$this->nameColumn],
                'what' => $occurences];
        }

        return $results;
    }

    /**
     * Odstraní nezámé sloupečky.
     *
     * @param array $data
     */
    public function unsetUnknownColumns($data = null)
    {
        if (is_null($data)) {
            $data = $this->getData();
        }
        foreach ($data as $column => $value) {
            if ($column == $this->keyColumn) {
                continue;
            }
            if (!isset($this->keywordsInfo[$column])) {
                $this->unsetDataValue($column);
            } else {
                if (isset($this->keywordsInfo[$column]['select'])) {
                    $this->unsetDataValue($column);
                }
            }
        }
    }

    /**
     * Načte z MySQL data k aktuálnímu $ItemID.
     *
     * @param int $itemID klíč záznamu
     *
     * @return array Results
     */
    public function getDataFromSQL($itemID = null)
    {
        if (is_null($itemID)) {
            $itemID = $this->getMyKey();
        }
        if (is_string($itemID)) {
            $itemID = "'".$this->easeAddSlashes($itemID)."'";
        } else {
            $itemID = $this->easeAddSlashes($itemID);
        }
        if (is_null($itemID)) {
            $this->error('loadFromSQL: Unknown Key', $this->data);
        }

        $queryRaw = $this->getListingQuerySelect().'  WHERE '.$this->getListingQueryWhere().' `'.$this->getKeyColumn().'`='.$itemID;

        return $this->dblink->queryToArray($queryRaw);
    }

    /**
     * Připraví fragment SQL pro výběr všech registrovaných.
     *
     * @param string $table
     * @param array  $columns Pole chlívků
     *
     * @return string
     */
    public function sqlColumnsToSelect($table = null, $columns = null)
    {
        $columnsToRender = [];
        if (is_null($columns)) {
            $columns = $this->keywordsInfo;
            if (!array_key_exists($this->keyColumn, $columns)) {
                $columns[$this->keyColumn] = $this->keyColumn;
            }
        }

        foreach ($columns as $column => $column_nfo) {
            if (isset($this->keywordsInfo[$column]['select'])) {
                $columnsToRender[] = $this->keywordsInfo[$column]['select'].' AS '.$column;
            } else {
                if ($table) {
                    $columnsToRender[] = $table.'.`'.$column.'` AS '.$column;
                } else {
                    $columnsToRender[] = '`'.$column.'`';
                }
            }
        }

        return implode(',', $columnsToRender);
    }

    /**
     * Sql fragment dotazu specifický objekt.
     */
    public function getWhere()
    {
        return;
    }

    /**
     * Vrací sql fragment pro vrácení SQL volání hlavního sloupečku.
     *
     * @return string
     */
    public function getKeySelect()
    {
        $keyColumn = $this->getKeyColumn();
        if (isset($this->keywordsInfo[$keyColumn]['select'])) {
            return $this->keywordsInfo[$keyColumn]['select'];
        }

        return $keyColumn;
    }

    /**
     * Uchová obsah všech záznamu.
     *
     * @param array $data
     */
    public function setListingData($data)
    {
        $this->listingData = $data;
    }

    /**
     * Vrací obsah všech záznamu.
     *
     * @return array
     */
    public function getListingData()
    {
        return $this->listingData;
    }

}
