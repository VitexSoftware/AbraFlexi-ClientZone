<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ClientZone\ui;

/**
 * Description of RecordsWithLabel
 *
 * @author vitex
 */
class RecordsWithLabel extends \Ease\TWB5\Container
{
    public $label = '';
    public $engine = null;

    public function __construct($engine, $label)
    {
        $this->label  = $label;
        $this->engine = $engine;
        $records      = $engine->getColumnsFromAbraFlexi(['id', 'nazev', 'stitky',
            'kod'], ['stitky' => $label]);
        $listing = new \Ease\Html\TableTag(null, ['class' => 'table']);
        if (count($records)) {
            $engine->addStatusMessage(sprintf(_('%s records with label %s'),
                    count($records), $label));

            foreach ($records as $record) {
                $recordToShow = [];

                $evidence = $this->engine->getEvidence();

                $recordToShow['flexiBee'] = new \Ease\TWB5\LinkButton($engine->getEvidenceURL().'/'.$record['id'],
                    new \Ease\Html\ImgTag('images/flexibee.png', 'FlexiBee',
                    ['width' => '20px']).' '.$record['kod'], 'info');
                $recordToShow['nazev']    = '<a href="'.$evidence.'.php?id='.$record['id'].'"><strong>'.$record['nazev'].'</strong></a>';
                $recordToShow['stitky']   = $this->labelLinks($record['stitky']);
                $listing->addRowColumns($recordToShow);
            }
        }
        parent::__construct($listing);
    }

    public function labelLinks($labels)
    {
        $labelLinks = '';
        foreach (explode(',', $labels) as $label) {
            $label = trim($label);
            if ($label == $this->label) {
                $labelText = '<strong>'.$label.'</strong>';
            } else {
                $labelText = $label;
            }
            $labelLinks .= ' <a href="labels.php?label='.$label.'&evidence='.$this->engine->getEvidence().'">'.$labelText.'</a> ';
        }
        return $labelLinks;
    }
}
