<?php
/**
 * clientzone - Vršek stránky.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017 VitexSoftware v.s.cz
 */

namespace ClientZone\ui;

class MainPageMenu extends \Ease\ui\MainPageMenu
{
    /**
     * Add Item to mainpage Menu
     * 
     * @param string $image url
     * @param string $title caption
     * @param string $url   image link href url
     * 
     * @return \Ease\Html\ATag
     */
    public function addMenuItem($image, $title, $url)
    {
        return $this->row->addItem(
                new \Ease\Html\ATag(
                $url,
                new \Ease\TWB5\Col(2,
                "$title<center><img class=\"mpicon img-responsive\" src=\"$image\" alt=\"$title\"></center>")
                )
        );
    }
}
