<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ClientZone\ui;

/**
 * Description of LinkToFlexiBeeButton
 *
 * @author vitex
 */
class LinkToFlexiBeeButton extends \Ease\TWB5\LinkButton
{

    /**
     *
     * @param \AbraFlexi\RO $flexibee
     */
    public function __construct($flexibee, $properties = [])
    {
        parent::__construct($flexibee->getAbraFlexiURL(),
            [new \Ease\Html\ImgTag('images/flexibee.png', _('Open in FlexiBee'),
        $properties), ' ', \AbraFlexi\EvidenceList::$name[$flexibee->getEvidence()].' <strong>'.str_replace('code:',
                '', $flexibee).'</strong>'], 'warning');
    }

}
