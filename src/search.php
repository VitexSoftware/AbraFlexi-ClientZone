<?php

namespace ClientZone;

/**
 * clientzone - Search.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017 VitexSoftware v.s.cz
 */
require_once 'includes/Init.php';



$evidence = $oPage->getRequestValue('evidence');
$query    = $oPage->getRequestValue('search');

$oPage->addItem(new ui\PageTop(_('Search results').': '.$query));

if (strlen($query) > 1) {
    $searcher = new Searcher($evidence);
    $results  = $searcher->searchAll($query);

    if ((count($results) === 1) && (count(current($results)) === 1)) {
        if (isset($results[key($results)][0]['url'])) {
            $oPage->redirect($results[key($results)][0]['url']);
        }
    }

    if (count($results)) {
        $searcher->addStatusMessage(sprintf(_('%d occurencies of %s found'),
                count($results), $query), 'info');
        $resultTables = [];
        foreach ($results as $evidenceName => $evidenceResults) {
            $resultTables[] = new \Ease\Html\H3Tag($evidenceName);
            $resultTable    = new \Ease\Html\TableTag(null, ['class' => 'table']);
            $columnNames    = array_keys(current($evidenceResults));
            if (count($columnNames) > 4) {
                array_pop($columnNames);
                array_pop($columnNames);
                array_pop($columnNames);
            }
            array_pop($columnNames);
            $resultTable->addRowHeaderColumns($columnNames);
            foreach ($evidenceResults as $key => $values) {
                $destA = '<a href="'.$values['what'].'.php?id='.$values['id'].'">';
                foreach ($values as $vkey => $vvalue) {
                    if (is_array($vvalue)) {
                        $vvalue = implode('|', $vvalue);
                    }

                    $values[$vkey] = $destA.str_replace($query,
                            "<strong style=\"background-color: yellow\">$query</strong>",
                            $vvalue).
                        '</a>';
                }
                if (count($columnNames) > 4) {
                    unset($values['what']);
                    unset($values['name']);
                }
                unset($values['url']);
                $resultTable->addRowColumns($values);
            }
            $resultTables[] = $resultTable;
        }
        $oPage->container->addItem(new \Ease\TWB5\Panel(sprintf(_('Search for %s results in %s'),
                "<strong>$query</strong>", $evidenceName), 'info', $resultTables));
    }
}




$oPage->addItem(new ui\PageBottom());

$oPage->draw();
