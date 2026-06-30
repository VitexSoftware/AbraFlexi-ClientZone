<?php
/**
 * clientzone - Třída zdroje dat FlexiGridu.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017 VitexSoftware v.s.cz
 */
//define('K_PATH_IMAGES', dirname(__DIR__) . '/img/');
//require_once 'tcpdf/tcpdf.php';

/**
 * Description of DBFDataSource.
 *
 * @author vitex
 */

namespace ClientZone;

class DataSource extends \Ease\Brick
{
    public $charset   = 'WINDOWS-1250//TRANSLIT';
    public $incharset = 'UTF-8';
    public $filename  = 'export';
    public $columns   = [];

    /**
     * PDF wrapper.
     *
     * @var TCPDF
     */
    private $pdf = null;

    /**
     * Titul exportu.
     *
     * @var string
     */
    private $title = '';

    /**
     * Url pro odskok při editačních akcích.
     *
     * @var string
     */
    public $fallBackUrl = '';

    /**
     * Instance objektu webové stránky.
     *
     * @var EaseWebPage
     */
    public $webPage = null;

    /**
     * Data určena k znovunaplnění formuláře v případě chyby.
     *
     * @var array
     */
    public $fallBackData = [];

    /**
     * @var type
     */
    private $order = null;

    /**
     * objekt poskytující data.
     *
     * @var DBFBase
     */
    public $handledObejct = null;

    /**
     * Vrací data pro Grid.
     *
     * @param DBFBase $handledObejct objekt poskytující data
     * @param string  $fallBackUrl
     */
    public function __construct($handledObejct, $fallBackUrl = null)
    {
        $this->handledObejct = $handledObejct;
        parent::__construct();
        $this->setBackUrl($fallBackUrl);
        $this->webPage       = \Ease\WebPage::singleton();
        $this->title         = $this->webPage->getRequestValue('title');
        if ($this->title) {
            $this->filename = preg_replace('/[^0-9^a-z^A-Z^_^.]/', '',
                str_replace(' ', '_', $this->title));
        }

        $cols = $this->webPage->getRequestValue('cols');
        if ($cols) {
            $col           = explode('|', $cols);
            $names         = $this->webPage->getRequestValue('names');
            $nam           = explode('|', urldecode($names));
            $this->columns = array_combine($col, $nam);
        }

        $this->ajaxify();
    }

    public function setOrder($order)
    {
        $this->order = $order;
    }

    /**
     * Nastaví URL pro znovuzobrazení stránky.
     *
     * @param type $url
     */
    public function setBackUrl($url)
    {
        $this->fallBackUrl = $url;
    }

    /**
     * řešení.
     */
    public function ajaxify()
    {
        $action = $this->webPage->getRequestValue('action');

        if ($action) {
            if ($this->controlColumns()) {
                switch ($action) {
                    case 'delete':
                        if ($this->controlDeleteColumns()) {
                            $this->fallBackUrl = false;
                            if ($this->deleteFromSQL()) {
                                $this->webPage->addStatusMessage(_('Smazáno'));
                            }
                        }
                        break;
                    case 'add':
                        if ($this->controlAddColumns()) {
                            if ($this->insertToMySQL()) {
                                $this->webPage->addStatusMessage(_('Záznam byl přidán'),
                                    'success');
                            } else {
                                $this->webPage->addStatusMessage(_('Záznam nebyl přidám'),
                                    'error');
                            }
                        }
                        break;
                    case 'edit':
                        if ($this->controlEditColumns()) {
                            if ($this->saveToMySQL()) {
                                $this->webPage->addStatusMessage(_('Záznam byl upraven'),
                                    'success');
                            } else {
                                $this->webPage->addStatusMessage(_('Záznam nebyl upravn'),
                                    'error');
                            }
                        }
                        break;

                    default:
                        break;
                }
            }
            if ($this->fallBackUrl) {
                $this->webPage->redirect(EasePage::arrayToUrlParams($this->fallBackData,
                        $this->fallBackUrl));
                exit();
            }
        }
    }

    /**
     * Vrací celkový počet výsledků dotazu bez stránkování.
     *
     * @param string $queryRaw Select fragment
     * @param string $where    Where fragment
     *
     * @return int
     */
    public function getTotal($queryRaw, $where = null)
    {
        $total = 0;
        if (strstr($queryRaw, 'GROUP BY')) {
            $pattern = '/SELECT(.*)FROM(.*)GROUP BY(.*)/i';
            if ($where) {
                $replacement = 'SELECT COUNT(*) FROM $2 WHERE '.str_replace('WHERE',
                        '', $where).' GROUP BY $3 ';
            } else {
                $replacement = 'SELECT COUNT(*) FROM $2 GROUP BY $3 ';
            }
        } else {
            $pattern     = '/SELECT(.*)FROM(.*)/i';
            $replacement = 'SELECT COUNT(*) FROM $2';
            if ($where) {
                $replacement .= ' WHERE '.str_replace('WHERE', '', $where);
            }
        }
        $queryRaw = preg_replace($pattern, $replacement, $queryRaw);

        $counts = $this->handledObejct->dblink->queryToArray($queryRaw);
        if (count($counts) > 1) {
            $total = count($counts);
        } else {
            $total = current($counts[0]);
        }

        return $total;
    }

    public function getWhere()
    {
        $sourceWhere = null;
        $where       = '';
        if (method_exists($this->handledObejct, 'getWhere')) {
            $sourceWhere = $this->handledObejct->getWhere();
        }

        $query = isset($_REQUEST['query']) ? $_REQUEST['query'] : false;
        $qtype = isset($_REQUEST['qtype']) ? $_REQUEST['qtype'] : false;
        $query = $this->handledObejct->dblink->EaseAddSlashes($query);
        $qtype = $this->handledObejct->dblink->EaseAddSlashes($qtype);

        if (($query !== false) && strlen($query) && ($qtype !== false)) {
            $type = $this->handledObejct->useKeywords[$qtype];
            if ($qtype == $this->handledObejct->keyColumn) {
                $type = 'INT';
            }

            if (isset($this->handledObejct->keywordsInfo[$qtype]['select'])) {
                $qtype = $this->handledObejct->keywordsInfo[$qtype]['select'];
            }

            switch ($type) {
                case 'BOOL':
                case 'BOOLEAN':
                case 'INT':
                    $where = ' WHERE '.$qtype." = '".$query."'";
                    break;
                default:
                    $where = ' WHERE '.$qtype." LIKE  '%".$query."%'";
                    break;
            }
        }

        if ($sourceWhere) {
            if (strlen($where)) {
                $where .= ' AND '.$sourceWhere;
            } else {
                $where = ' WHERE '.$sourceWhere;
            }
        }

        return $where;
    }

    /**
     * @param string $queryRaw
     * @param string $transform html|csv|none
     *
     * @return array
     */
    public function getListing($queryRaw, $transform = 'html')
    {
        $page     = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
        $rp       = isset($_REQUEST['rp']) ? $_REQUEST['rp'] : 10;
        $sortname = isset($_REQUEST['sortname']) ? $_REQUEST['sortname'] : $this->handledObejct->getKeySelect();

        if (!$sortname) {
            $sortname = $this->handledObejct->getKeySelect();
        }

        $sortorder = isset($_REQUEST['sortorder']) ? $_REQUEST['sortorder'] : 'desc';
        $where     = str_replace('WHERE', '', $this->getWhere());

        if (strstr($sortname, '.')) {
            $sortname = '`'.str_replace('.', '`.`', $sortname).'`';
        }

        $sort  = " ORDER BY $sortname $sortorder";
        $start = (($page - 1) * $rp);

        $limit = " LIMIT $start, $rp";

        if (strstr($queryRaw, 'GROUP BY')) {
            $pattern = '/(.*)FROM(.*)GROUP BY(.*)/i';
            if ($where) {
                $replacement = '$1 FROM $2 WHERE '.$where.' GROUP BY $3 ';
            } else {
                $replacement = '$1 FROM $2 GROUP BY $3 ';
            }
            $queryRaw = preg_replace($pattern, $replacement, $queryRaw);
            $query    = "$queryRaw $sort $limit";
        } else {
            if ($where) {
                $query = "$queryRaw WHERE ".$where." $sort $limit";
            } else {
                $query = "$queryRaw $sort $limit";
            }
        }

        switch ($transform) {
            case 'csv':
                $resultRaw = $this->handledObejct->csvizeData($this->handledObejct->dblink->queryToArray($query));
                break;
            case 'html':
                $resultRaw = $this->handledObejct->htmlizeData($this->handledObejct->dblink->queryToArray($query));
                break;

            default:
                $resultRaw = $this->handledObejct->dblink->queryToArray($query);
                break;
        }

        if (!count($this->columns)) {
            return $resultRaw;
        }

        $result = [];
        foreach ($resultRaw as $rrid => $resultRow) {
            foreach ($this->columns as $colKey => $colValue) {
                if (isset($resultRow[$colKey])) {
                    $result[$rrid][$colKey] = $resultRow[$colKey];
                } else {
                    $result[$rrid][$colKey] = '';
                }
            }
        }

        return $result;
    }

    /**
     * @param type $queryRaw
     */
    public function getJson($queryRaw)
    {
        $rows = $this->webPage->getRequestValue('rows');
        if ($rows) {
            header('Content-type: application/json');
            if ($rows[strlen($rows) - 1] == ',') {
                $rows = substr($rows, 0, -1);
            }
            if ($this->order) {
                $order = ' ORDER BY '.$this->order;
            } else {
                $order = '';
            }
            $transactions = $this->handledObejct->dblink->queryToArray($queryRaw.' WHERE `'.$this->handledObejct->keyColumn.'` IN('.$rows.')'.$order,
                $this->handledObejct->getKeyColumn());
            $total        = count(explode(',', $rows));
        } else {
            $total        = $this->getTotal($queryRaw, $this->getWhere());
            $transactions = $this->getListing($queryRaw, 'html');
        }
        $page     = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
        $jsonData = ['page' => $page, 'total' => $total, 'rows' => []];
        if (count($transactions)) {
            foreach ($transactions as $row) {
                $entry = [
                    'id' => $row[$this->handledObejct->keyColumn],
                    'cell' => $row,
                ];
                if (isset($row['color'])) {
                    $entry['color'] = $row['color'];
                }
                if (isset($row['note'])) {
                    $entry['title'] = $row['note'];
                }

                $jsonData['rows'][] = $entry;
            }
        }

        return json_encode($jsonData);
    }

    /**
     * Převede pole záznamů na JSON pro FelexiGrid.
     *
     * @param array $transactions
     *
     * @return string json
     */
    public function dataToJson($transactions)
    {
        $total    = count($transactions);
        $page     = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
        $jsonData = ['page' => $page, 'total' => $total, 'rows' => []];
        if (count($transactions)) {
            foreach ($transactions as $row) {
                $entry = [
                    'id' => $row[$this->handledObejct->keyColumn],
                    'cell' => $row,
                ];
                if (isset($row['color'])) {
                    $entry['color'] = $row['color'];
                }
                if (isset($row['note'])) {
                    $entry['title'] = $row['note'];
                }

                $jsonData['rows'][] = $entry;
            }
        }

        return json_encode($jsonData);
    }

    public function getCsv($queryRaw)
    {
        $transactions = self::getListing($queryRaw, 'csv');
        $this->getCSVFile($transactions);
    }

    public function getPdf($queryRaw)
    {
        $transactions = self::getListing($queryRaw);
        $this->pdfInit($this->title);
        $this->getPDFFile($transactions, array_values($this->columns));
    }

    public function getCSVFromArray($array, $header = null)
    {
        if (is_null($header)) {
            $header = array_values($this->columns);
        }

        $output = '';
        for ($i = -1; $i < count($array); ++$i) {
            if ($i == -1 && is_array($header)) {
                $row = $header;
            } elseif ($i == -1) {
                continue;
            } else {
                $row = $array[$i];
            }
            $row_array = [];
            foreach ($row as $cell) {
                $row_array[] = $this->getCSVCell($cell);
//        $output .= $this->getCSVCell($cell);
            }
            $output .= implode(';', $row_array)."\n";
        }
        if (strtoupper($this->charset) != strtoupper($this->incharset)) {
            $output = iconv($this->incharset, $this->charset, $output);
        }

        return $output;
    }

    public function getCSVFile($array, $header = null)
    {
        // Output
        header('Content-type: text/x-csv');
//header("Content-type: text/csv");
        header('Content-type: application/csv; charset=win-1250');
        header('Cache-Control: maxage=3600');
        header('Pragma: public');
        header('Content-Disposition: attachment; filename = '.$this->filename.'.csv');
        echo $this->getCSVFromArray($array, $header);
    }

    /**
     * Vrací buňku CSV.
     *
     * @param int|string $cell
     *
     * @return string
     */
    public function getCSVCell($cell)
    {
        if ($cell == '') {
            return '';
        }
        $cell = preg_replace('/\<br(\s*)?\/?\>/i', "\r", $cell);
        $cell = preg_replace('/"/ms', '\"', $cell);
        if (preg_match('/;/ms', $cell)) {
            $cell = '"'.addslashes($cell).'"';
        }
        if (is_numeric($cell)) {
            $cell = str_replace('.', ',', $cell);
        }

        return $cell;
    }

    /**
     * Vypíše výsledek SQL dotazu v požadovaném tvaru.
     *
     * @param type $queryRaw
     */
    public function output($queryRaw = null)
    {
        if (is_null($queryRaw)) {
            $queryRaw = $this->handledObejct->getListingQuerySelect();
        }
        switch (\Ease\WebPage::singleton()->getRequestValue('export')) {
            case 'csv':
                $this->getCsv($queryRaw);
                break;
            case 'pdf':
                $this->getPdf($queryRaw);
                break;

            default:
                // header("Content-type: application/json");
                if (isset($this->handledObejct->evidence)) {
                    $this->handledObejct->loadFlexiData('detail=full');
                    echo $this->dataToJson($this->handledObejct->htmlizeData($this->handledObejct->getListingData()));
                } else {
                    echo $this->getJson($queryRaw);
                }
                break;
        }
    }

    /**
     * Init PDF exportu.
     *
     * @param string $title       nadpis stránky
     * @param char   $orientation P|L
     */
    public function pdfInit($title = null, $orientation = 'P')
    {
        $this->filename .= $title;

// pdf object
        $this->pdf = new TCPDF($orientation);

// set document information
        $this->pdf->SetCreator(PDF_CREATOR);
        $this->pdf->SetAuthor(\Ease\Shared::user()->getUsername());
        $this->pdf->SetTitle($title);
        $this->pdf->SetSubject('');
        $this->pdf->SetKeywords($title);

// set default header data
        $this->pdf->SetHeaderData('logo.png', 45, $title,
            "DB Finance s.r.o - nezávislý investiční zprostředkovatel a pojišťovací makléř\n"
            .'✉ dbfinance@dbfinance.cz ☎ 222 541 990 – 995 ⌨ www.dbfinance.cz ');
// set header and footer fonts
        $this->pdf->setHeaderFont(['dejavusans', '', 8]);
        $this->pdf->setFooterFont([PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA]);

// set default monospaced font
        $this->pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

//set margins
        $this->pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $this->pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $this->pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

//set auto page breaks
        $this->pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);

//set image scale factor
        $this->pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// ---------------------------------------------------------
// set default font subsetting mode
        $this->pdf->setFontSubsetting(true);

// Set font
// dejavusans is a UTF-8 Unicode font, if you only need to
// print standard ASCII chars, you can use core fonts like
// helvetica or times to reduce file size.
        $this->pdf->SetFont('dejavusans', '', 8, '', true);

// Add a page
// This method has several options, check the source code documentation for more information.
        $this->pdf->AddPage();

// ---------------------------------------------------------
// Close and output PDF document
// This method has several options, check the source code documentation for more information.
    }

    public function getPDFFromArray($array, $header = null)
    {
        $tbl = '<table>';

        $tbl .= '<tr>';
        foreach ($header as $h) {
            $tbl .= '<th style="font-weight: bold;">'.$h.'</th>';
        }
        $tbl .= '</tr>';

        foreach ($array as $row) {
            $tbl .= '<tr>';
            foreach ($row as $d) {
                $tbl .= '<td>'.$d.'</td>';
            }
            $tbl .= '</tr>';
        }

        $tbl .= '</table>';

        return $this->pdf->writeHTML($tbl, true, false, false, false, '');
    }

    public function getPDFFile($array, $header = null)
    {
        // Output
//        header("Content-type: text/x-csv");
//        //header("Content-type: text/csv");
//        //header("Content-type: application/csv");
//        header("Cache-Control: maxage=3600");
//        header("Pragma: public");
//        header("Content-Disposition: attachment; filename = " . $this->filename . ".csv");
        $this->getPDFFromArray($array, $header);

        $this->pdf->Output($this->filename.'.pdf', 'I');
    }

    /**
     * Zkontroluje obecná vstupní data.
     *
     * @return bool
     */
    public function controlColumns()
    {
        return true;
    }

    /**
     * Zkontroluje splnění podmínek pro smazání záznamu.
     *
     * @return bool
     */
    public function controlDeleteColumns()
    {
        $id = \Ease\WebPage::singleton()->getRequestValue($this->keyColumn);
        if ($id) {
            $this->setMyKey($id);

            return true;
        }

        return false;
    }

    /**
     * Zkontroluje podmínky pro přidání záznamu.
     *
     * @return bool
     */
    public function controlAddColumns()
    {
        return true;
    }

    /**
     * Zkontroluje podmínky pro editaci záznamu.
     *
     * @return bool
     */
    public function controlEditColumns()
    {
        $id = $this->webPage->getRequestValue($this->keyColumn);
        if ($id) {
            $this->setMyKey($id);

            return true;
        } else {
            return false;
        }
    }
}
