<?php
/**
 * Flexplorer - Browsing History.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017 VitexSoftware v.s.cz
 */

namespace ClientZone\ui;

/**
 * Description of History
 *
 * @author vitex
 */
class History extends \Ease\Html\DivTag
{
    public function __construct($content = null, $properties = null)
    {
        if (is_null($properties)) {
            $properties = [];
        }
        $properties['id'] = 'history';
        $properties['class'] = trim(($properties['class'] ?? '').' cz-history');

        if (!isset($_SESSION['history'])) {
            $_SESSION['history'] = [];
        }

        parent::__construct(null, $properties);

        foreach ($_SESSION['history'] as $bookmark) {
            $this->addItem(new \Ease\Html\Span(new \Ease\Html\ATag($bookmark['url'],
                [new \Ease\Html\SpanTag('', ['class' => 'fa fa-bookmark']), ' '.$bookmark['title']]),
                ['class' => 'hitem']));
        }

        $currentUrl = $_SERVER['REQUEST_URI'];
        if (!count($_SESSION['history']) || current(end($_SESSION['history'])) != $currentUrl) {
            $_SESSION['history'][] = ['url' => $currentUrl, 'title' => \Ease\WebPage::singleton()->pageTitle];
        }

    }

    function finalize()
    {
        $this->addCss('
            .hitem { background-color: #B5FFC4; margin: 5px; border-radius: 15px 50px 30px 5px; padding-left: 3px; padding-right: 10px; }
            #history { margin: 5px; }
            ');
    }

}
