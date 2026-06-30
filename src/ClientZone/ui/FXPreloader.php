<?php
/**
 * clientzone - Formulář příjm/výdaje pokladny.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017 VitexSoftware v.s.cz
 */

namespace ClientZone\ui;

class FXPreloader extends \Ease\Html\Div
{

    public function __construct($id = null)
    {
        parent::__construct(null,
            ['class' => 'loader', 'data-initialize' => 'loader', 'id' => $id]);
    }

    public function finalize(): void
    {
        parent::finalize();
        \Ease\WebPage::singleton()->includeCss('twitter-bootstrap/css/fuelux.css',
            true);
        \Ease\WebPage::singleton()->includeJavascript('/javascript/twitter-bootstrap/fuelux.js');
        \Ease\WebPage::singleton()->addJavascript("$('#".$this->getTagID()."').loader();");
        \Ease\WebPage::singleton()->addCSS('
#'.$this->getTagID().'{
    position: absolute;
    top: 50%;
    left: 50%;
    margin-top: -50px;
    margin-left: -50px;
    width: 100px;
    height: 100px;
    visibility: hidden;
}​
            ');
    }
}