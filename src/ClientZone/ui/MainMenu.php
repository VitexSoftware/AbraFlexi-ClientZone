<?php
/**
 * clientzone - Hlavní menu.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017 VitexSoftware v.s.cz
 */

namespace ClientZone\ui;

class MainMenu extends \Ease\Html\NavTag
{

    /**
     * Vytvoří hlavní menu.
     */
    public function __construct()
    {
        parent::__construct(null, ['id' => 'MainMenu']);
    }

    /**
     * Data source.
     *
     * @param type   $source
     * @param string $icon   Description
     *
     * @return string
     */
    protected function getMenuList($source, $icon = '')
    {
        $keycolumn  = $source->getKeyColumn();
        $namecolumn = $source->nameColumn;
        $lister     = $source->getColumnsFromSQL([$source->getKeyColumn(), $namecolumn],
            null, $namecolumn, $keycolumn);

        $itemList = [];
        if ($lister) {
            foreach ($lister as $uID => $uInfo) {
                $itemList[$source->keyword.'.php?'.$keycolumn.'='.$uInfo[$keycolumn]]
                    = '<i class="fa fa-'.$icon.'"></i>'.'&nbsp;'.$uInfo[$namecolumn];
            }
        }

        return $itemList;
    }

    /**
     * Vložení menu.
     */
    public function afterAdd()
    {
        $nav    = $this->addItem(new BootstrapMenu());
        $user   = \Ease\Shared::user();
        $userID = \Ease\Shared::user()->getUserID();

        switch (get_class($user)) {
            case 'ClientZone\Customer': //Customer
                $nav->addMenuItem(new NavBarSearchBox('search', 'search.php'));
                $nav->addDropDownMenu('<img width=30 src=images/order.svg> '._('Orders'),
                    [
                        'orderform.php' => '<i class="fa fa-plus"></i>'.' '._('New order'),
//                        'pricelist.php' => '<i class="fa fa-list"></i>'.' '._('Pricelist'),
                        'myorders.php' => '<i class="fa fa-list"></i>'.'&nbsp;'._('My orders')]
                );

                break;
            case 'Ease\Anonym': //Anonymous
                $nav->addMenuItem( new \Ease\Html\SpanTag(_('ClientZone'),['style'=>'font-size: 35px; padding-top: 6px;']) );
            default:
                break;
        }
    }

    /**
     * Přidá do stránky javascript pro skrývání oblasti stavových zpráv.
     */
    public function finalize()
    {
        $this->addCss('body {
                padding-top: 60px;
                padding-bottom: 40px;
            }');

        \Ease\JQuery\Part::jQueryze($this);
        \Ease\Shared::webPage()->addCss('.dropdown-menu { overflow-y: auto } ');
        \Ease\Shared::webPage()->addJavaScript("$('.dropdown-menu').css('max-height',$(window).height()-100);",
            null, true);
        $this->includeJavaScript('js/slideupmessages.js');
    }
}
