<?php
/**
 * DBFinance - datagrid.
 *
 * @author     Vítězslav Dvořák <vitex@murka.cz>
 * @copyright  2017 VitexSoftware v.s.cz
 */

namespace ClientZone;

/**
 * Description of DBFDataGrid.
 *
 * @author Vítězslav Dvořák
 */
class DataGrid extends \Ease\Html\TableTag
{
    /**
     * Extra filtr výsledků.
     *
     * @var string
     */
    public $select;

    /**
     * Výchozí nastavení sloupečků.
     *
     * @var array
     */
    public $defaultColProp = ['sortable' => true];

    /**
     * Nastavení.
     *
     * @var array
     */
    public $options       = [
        'method' => 'GET',
        'dataType' => 'json',
        'height' => 'auto',
        'width' => 'auto',
        'sortname' => 'id',
        'sortorder' => 'asc',
        'usepager' => true,
        'useRp' => true,
        'qtype' => '',
        'rp' => 20,
        'dblClickResize' => true,
        'showTableToggleBtn' => true,
        'add' => [],
        'edit' => [],
        'buttons' => [
            ['name' => 'CSV Export', 'bclass' => 'csvexport'],
//        , array('name' => 'PDF Export', 'bclass' => 'pdfexport')
        ],
    ];
    public $addFormItems  = [['name' => 'action', 'value' => 'add', 'type' => 'hidden']];
    public $editFormItems = [['name' => 'action', 'value' => 'edit', 'type' => 'hidden']];

    /**
     * Objekt jehož data jsou zobrazována.
     *
     * @var IEcfg
     */
    public $dataSource = null;

    /**
     * Editovat záznam po doubleclicku.
     *
     * @var bool
     */
    public $dblclk2edit = true;

    /**
     * Zobrazovat klíčový sloupeček databáze.
     *
     * @var bool
     */
    public $showKeyColumn = false;

    /**
     * Zdroj dat pro flexigrid.
     *
     * @param string $name       ID elementu
     * @param string $datasource URL
     * @param array  $properties vlastnosti elementu
     */
    public function __construct($name, $datasource, $properties = null)
    {
        $wp = \Ease\WebPage::singleton();

//        $options = new DBFOptions();
//        $this->showKeyColumn = $options->getDataValue('tableids');

        if ($this->showKeyColumn) {
            $datasource->useKeywords  = array_merge([$datasource->keyColumn => 'INT'],
                $datasource->useKeywords);
            $datasource->keywordsInfo = array_merge([$datasource->keyColumn => ['title' => 'ID',
                    'width' => 40]], $datasource->keywordsInfo);
        }

        $this->dataSource           = $datasource;
        $this->options['title']     = $name;
        $this->options['myclass']   = get_class($this);
        $this->options['dataclass'] = addslashes(get_class($datasource));

        $this->setTagID();

        $this->options['url'] = 'datasource.php?class='.str_replace("\\", '_',
                get_class($datasource));

        $this->perset('rp');
        $this->perset('newp');
        $this->perset('sortorder');
        if (!$this->perset('sortname')) {
            $this->options['sortname'] = $datasource->getKeySelect();
        }

        $query = $wp->getRequestValue('query');
        $qtype = $wp->getRequestValue('qtype');

        if ($qtype && $query) {
            $this->options['query'] = $query;
            $this->options['qtype'] = $qtype;
        } else {
            $this->perset('query');
            $this->perset('qtype');
        }

        $dataurl = null;

        parent::__construct($dataurl, $properties);
        \Ease\Part::jQueryze();
//        $wp->includeJavaScript('/javascript/jquery-cookie/jquery.cookie.js');
        $wp->includeJavaScript('js/flexigrid.js');
        $wp->includeCSS('css/flexigrid.css');
        $this->setUpButtons();
        $this->setUpColumns();
        $this->addTagClass('ABDataGrid');
    }

    /**
     * Obnoví nastavení ze session.
     *
     * @param string $keyword
     */
    public function perset($keyword)
    {
        $gidentify = $this->options['myclass'].'_'.$this->options['dataclass'];
        if (isset($_SESSION['gridPreferences'][$gidentify][$keyword])) {
            $this->options[$keyword] = $_SESSION['gridPreferences'][$gidentify][$keyword];

            return true;
        }

        return false;
    }

    /**
     * Obnoví šíře sloupce ze session.
     *
     * @param string $keyword
     */
    public function persetWidth($keyword)
    {
        $width     = null;
        $gidentify = $this->options['myclass'].'_'.$this->options['dataclass'];
        if (isset($_SESSION['gridPreferences'][$gidentify]['width@'.$keyword])) {
            $width = $_SESSION['gridPreferences'][$gidentify]['width@'.$keyword];
        }

        return $width;
    }

    /**
     * Obnoví stav zobrazení sloupce ze session.
     *
     * @param string $keyword
     */
    public function persetVisibility($keyword)
    {
        $visibility = false;
        $gidentify  = $this->options['myclass'].'_'.$this->options['dataclass'];
        if (isset($_SESSION['gridPreferences'][$gidentify]['visibility@'.$keyword])) {
            $visibility = $_SESSION['gridPreferences'][$gidentify]['visibility@'.$keyword];
        }

        return $visibility;
    }

    /**
     * Nastaví tlačítka.
     */
    public function setUpButtons()
    {
        //$this->addSelectAllButton(_('Výběr'));
        $this->addAddButton(_('Přidat'));
        $this->addEditButton(_('Modify'));
        $this->addDeleteButton(_('Smazat'));
    }

    /**
     * Nastaví sloupce.
     */
    public function setUpColumns()
    {
        foreach ($this->dataSource->useKeywords as $keyword => $type) {
            $options = [];

            $hide = $this->persetVisibility($keyword);
            if (!is_null($hide)) {
                // $options['hide'] = $hide;
            }

            if (isset($this->dataSource->keywordsInfo[$keyword])) {
                $options = array_merge($options,
                    $this->dataSource->keywordsInfo[$keyword]);

                if (isset($this->dataSource->keywordsInfo[$keyword]['width'])) {
                    if (!isset($options['width']) || !$options['width']) {
                        $options['width'] = intval($this->dataSource->keywordsInfo[$keyword]['width']);
                    }
                } else {
                    switch ($type) { // Nastavení výchozích šířek sloupců dle typu
                        case 'DATE':
                            $options['width'] = 70;
                            break;
                        case 'DATETIME':
                            $options['width'] = 120;
                            break;
                        case 'BOOL':
                        case 'BOOLEAN':
                            $options['width'] = 50;
                            break;
                        default:
                            break;
                    }
                }

                $sessw = $this->persetWidth($keyword);
                if ($sessw) {
                    $options['width'] = $sessw;
                }

                if (!isset($this->dataSource->keywordsInfo[$keyword]['title']) || !strlen(trim($this->dataSource->keywordsInfo[$keyword]['title']))) {
                    //$this->addStatusMessage(_('Chybi titulek') . ' ' . $this->dataSource->keyword . ': ' . $keyword, 'warning');
                    $this->dataSource->keywordsInfo[$keyword]['title'] = $keyword;
                }

                $searchable = false;
                if (isset($options['searchable'])) {
                    $searchable = $options['searchable'];
                } else {
                    if (strstr($type, 'VARCHAR') || strstr($type, 'STRING') || strstr($type,
                            'TEXT') || strstr($type, 'SELECT') || strstr($type,
                            'PLATFORM') || strstr($type, 'IDLIST') || ($type = 'BOOL')
                        || ($type == 'DATE') || ($type == 'INT')) {
                        $searchable = true;
                    }
                }
                $this->setColumn($keyword,
                    $this->dataSource->keywordsInfo[$keyword]['title'],
                    $searchable, $options);
            }
        }
    }

    /**
     * Přidá tlačítko.
     *
     * @param string $title   Popisek tlačítka
     * @param string $class   CSS třída tlačítka
     * @param string $onpress Funkce spuštěná při stisku
     * @param string $icon    Obrázek na tlačítku
     */
    public function addButton($title, $class, $onpress = null, $icon = null)
    {
        if ($onpress) {
            $this->options['buttons'][] = ['name' => $title, 'bclass' => $class,
                'onpress: '.$onpress];
        } else {
            $this->options['buttons'][] = ['name' => $title, 'bclass' => $class];
        }

        if ($icon) {
            $this->addCSS('
.flexigrid div.fbutton .'.$class.' {
    background: url('.$icon.') no-repeat center left;
}
');
        }
    }

    /**
     * Vloží přidávací tlačítko.
     *
     * @param string $title  Nadpis gridu
     * @param string $target Url
     */
    public function addAddButton($title, $target = null)
    {
        $show = false;
        if (is_null($target)) {
            $target = $this->options['url'];
        }
        $this->addButton($title, 'add', 'addRecord');
        $this->addJavaScript('function addRecord(com, grid) {
              $(location).attr(\'href\',\''.$this->dataSource->keyword.'.php\');
            }
        ', null, true);
    }

    /**
     * Vloží editační tlačítko.
     *
     * @param type $title
     * @param type $target
     */
    public function addSelectAllButton($title, $target = null)
    {
        $this->addButton($title, 'selectAll', 'selectAll',
            'img/edit-select-all.svg');
        $this->addJavaScript('function selectAll(com, grid) {
                $(\'tr\', grid).each(function() {
                    $(this).click();
                });
}');
    }

    /**
     * Vloží editační tlačítko.
     *
     * @param type $title
     * @param type $target
     */
    public function addEditButton($title, $target = null)
    {
        $this->addButton($title, 'edit', 'editRecord');
        $this->addJavaScript('function editRecord(com, grid) {

        var numItems = $(\'.trSelected\').length
        if(numItems){
            if(numItems == 1) {
                $(\'.trSelected\', grid).each(function() {
                    var id = $(this).attr(\'id\');
                    id = id.substring(id.lastIndexOf(\'row\')+3);
                    $(location).attr(\'href\',\''.$this->dataSource->keyword.'.php?'.$this->dataSource->getKeyColumn().'=\' +id);
                });

            } else {
                $(\'.trSelected\', grid).each(function() {
                    var id = $(this).attr(\'id\');
                    id = id.substring(id.lastIndexOf(\'row\')+3);
                    var url =\''.$this->dataSource->keyword.'.php?'.$this->dataSource->getKeyColumn().'=\' +id;
                    var win = window.open(url, \'_blank\');
                    win.focus();
                });
            }
        } else {
            alert("'._('Je třeba označit nějaké řádky').'");
        }

            }
        ', null, true);
    }

    /**
     * Přidá tlačítko pro smazání záznamu.
     *
     * @param string $title  popisek tlačítka
     * @param string $target výkonný skript
     */
    public function addDeleteButton($title, $target = null)
    {
        if (is_null($target)) {
            $target = $this->options['url'];
        }
        $this->addButton($title, 'delete', 'deleteRecord');
        $this->addJavaScript('function deleteRecord(com, grid) {

        var numItems = $(\'.trSelected\').length
        if(numItems){
            if(numItems == 1) {
                $(\'.trSelected\', grid).each(function() {
                    var id = $(this).attr(\'id\');
                    id = id.substring(id.lastIndexOf(\'row\')+3);
                    $(location).attr(\'href\',\''.$this->dataSource->keyword.'.php?action=delete&'.$this->dataSource->getKeyColumn().'=\' +id);
                });

            } else {
                $(\'.trSelected\', grid).each(function() {
                    var id = $(this).attr(\'id\');
                    id = id.substring(id.lastIndexOf(\'row\')+3);
                    var url =\''.$this->dataSource->keyword.'.php?action=delete&'.$this->dataSource->getKeyColumn().'=\' +id;
                    var win = window.open(url, \'_blank\');
                    win.focus();
                });
            }
        } else {
            alert("'._('Je třeba označit nějaké řádky').'");
        }


            }
        ', null, true);
    }

    /**
     * Vloží duplikační tlačítko.
     *
     * @param type $title
     * @param type $target
     */
    public function addDuplicateButton($title, $target = null)
    {
        $this->addButton($title, 'duplicate', 'duplicate');
        $this->addJavaScript('function duplicate(com, grid) {

        var numItems = $(\'.trSelected\').length
        if(numItems){
            if(numItems == 1) {
                $(\'.trSelected\', grid).each(function() {
                    var id = $(this).attr(\'id\');
                    id = id.substring(id.lastIndexOf(\'row\')+3);
                    $(location).attr(\'href\',\''.$this->dataSource->keyword.'.php?action=duplicate&'.$this->dataSource->getKeyColumn().'=\' +id);
                });

            } else {
                $(\'.trSelected\', grid).each(function() {
                    var id = $(this).attr(\'id\');
                    id = id.substring(id.lastIndexOf(\'row\')+3);
                    var url =\''.$this->dataSource->keyword.'.php?action=duplicate&'.$this->dataSource->getKeyColumn().'=\' +id;
                    var win = window.open(url, \'_blank\');
                    win.focus();
                });
            }
        } else {
            alert("'._('Je třeba označit nějaké řádky').'");
        }

            }
        ', null, true);
    }

    /**
     * Nastaví parametry sloupečků.
     *
     * @param string $name             jméno z databáze
     * @param string $title            popisek sloupce
     * @param bool   $search           nabídnout pro sloupec vyhledávání
     * @param array  $columnProperties další vlastnosti v poli
     */
    public function setColumn($name, $title, $search = false,
                              $columnProperties = null)
    {
        if (!isset($this->options['colModel'])) {
            $this->options['colModel'] = [];
        }
        if (!isset($columnProperties['editable'])) {
            $columnProperties['editable'] = false;
        }
        $properties            = $this->defaultColProp;
        $properties['name']    = $name;
        $properties['display'] = $title;
        if (is_array($columnProperties)) {
            $this->options['colModel'][] = array_merge($properties,
                $columnProperties);
        } else {
            $this->options['colModel'][] = $properties;
        }
        if ($search) {
            if (is_array($search)) {
                foreach ($search as $sid => $srch) {
                    $search[$sid] .= ' LIKE "%"';
                }
                $search = implode(' OR ', $search);
            }

            $this->options['searchitems'][] = ['display' => $title, 'name' => $name,
                'isdefault' => ($this->options['qtype'] == $name), 'where' => addslashes($search)];
        }

        if ($columnProperties['editable']) {
            if (!isset($columnProperties['label'])) {
                $columnProperties['label'] = $title;
            }
            if (!isset($columnProperties['value'])) {
                $columnProperties['value'] = $this->webPage->getRequestValue($name);
            }
            $columnProperties['name']   = $name;
            $this->editFormItems[$name] = $columnProperties;
            $this->addFormItems[$name]  = $columnProperties;
        }
    }

    /**
     * Vložení skriptu.
     */
    public function finalize(): void
    {
        parent::finalize();

        //Patch Grid Responisive
        $grid_js = '
        var grids=[];
            $(window).resize(function() {
                //Resize all the grids on the page
                //Only resize the ones whoes size has actually changed...
                for(var i in grids) {
                    if(grids[i].width!=grids[i].$grid.width()) {
                        sizeGrid(grids[i]);
                    }
                }
            });';
        $grid_js .= '
            //Keep track of all grid elements and current sizes
            function addGrid($table, grid) {
                var $grid = $table.closest(\'.flexigrid\');
                var data = {$table:$table, $grid:$grid, grid:grid, width:$grid.width()};
                grids.push(data);
                sizeGrid(data);
            }';
        $grid_js .= '
            //Make all cols with auto size fill remaining width..
            function sizeGrid(data) {
                //Auto size the middle col.
                var totalWidth = data.$grid.outerWidth()-15; //15 padding - not found where this is set

                var fixedWidth = 0;
                var fluidCols = [];
                for(var i=0; i<data.grid.colModel.length; i++ ) {
                    if( !isNaN(data.grid.colModel[i].width) ) {
                        fixedWidth+=data.$table.find(\'tr:eq(\'+i+\') td:eq(\'+i+\'):visible\').outerWidth(true);
                    } else {
                        fluidCols.push(i);
                    }
                }

                var newWidth = (totalWidth-fixedWidth)/fluidCols.length;
                for(var i in fluidCols) {
                    data.grid.g.colresize = { n:fluidCols[i], nw:newWidth };
                    data.grid.g.dragEnd( );
                }

                data.width = data.$grid.width();
            }';

        if ($this->select) {
            $this->options['query'] = current($this->select);
            $this->options['qtype'] = key($this->select);
        }

        if ($this->dblclk2edit) {
            $this->options['onDoubleClick'] = 'function(g) {
                    var id = $(g).attr(\'id\');
                    id = id.substring(id.lastIndexOf(\'row\')+3);
                    $(location).attr(\'href\',\''.$this->dataSource->keyword.'.php?'.$this->dataSource->getKeyColumn().'=\' +id);

            }';
        }

        $grid_id = $this->getTagID();

        $this->options['onSuccess']    = 'function() { addGrid($("#'.$grid_id.'"), this)}';
        $this->options['getGridClass'] = 'function(g) { this.g=g; return g; }';
        \Ease\WebPage::singleton()->addJavaScript("\n"
            .'$(\'#'.$grid_id.'\').flexigrid({ '.\Ease\Part::partPropertiesToString($this->options).' }); '.$grid_js,
            null, true);

        \Ease\WebPage::singleton()->includeJavaScript('js/jquery.visible.js');
        \Ease\WebPage::singleton()->includeJavaScript('js/flexithfloat.js');

//        \Ease\WebPage::singleton()->addCSS(' .shown { position: absolute; top: 200; z-index: 1; }');
    }
}