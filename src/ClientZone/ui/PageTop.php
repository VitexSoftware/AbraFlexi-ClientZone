<?php
/**
 * clientzone - Vršek stránky.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2019 VitexSoftware v.s.cz
 */

namespace ClientZone\ui;

/**
 * Page TOP.
 */
class PageTop extends \Ease\Html\DivTag
{
    /**
     * Titulek stránky.
     *
     * @var type
     */
    public $pageTitle = 'Page Heading';

    /**
     * Nastavuje titulek.
     *
     * @param string $pageTitle
     */
    public function __construct($pageTitle = null)
    {
        parent::__construct();
        if (!is_null($pageTitle) && isset($GLOBALS['oPage'])) {
            $GLOBALS['oPage']->setPageTitle($pageTitle);
        }
    }

    /**
     * Vloží vršek stránky a hlavní menu.
     */
    public function finalize(): void
    {
        parent::finalize();
        $this->addItem(new MainMenu());

        if (get_class(\Ease\Shared::user()) == 'ClientZone\User') {
            $this->addItem(new History());
        }
    }

}
