<?php

/**
 * clientzone - Main page icon menu.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2024 VitexSoftware v.s.cz
 */

namespace ClientZone\ui;

class MainPageMenu extends \Ease\Html\DivTag
{
    public \Ease\Html\DivTag $row;

    public function __construct()
    {
        parent::__construct(null, ['class' => 'container-fluid py-3']);
        $this->row = $this->addItem(new \Ease\Html\DivTag(null, ['class' => 'row g-3 justify-content-center']));
    }

    /**
     * Add icon tile to the main page menu.
     *
     * @param string $image image URL
     * @param string $title caption
     * @param string $url   link href
     *
     * @return \Ease\Html\ATag
     */
    public function addMenuItem(string $image, string $title, string $url): \Ease\Html\ATag
    {
        $col = $this->row->addItem(new \Ease\Html\DivTag(null, ['class' => 'col-6 col-sm-4 col-md-3 col-lg-2 text-center']));
        $link = $col->addItem(new \Ease\Html\ATag($url, null, ['class' => 'text-decoration-none']));
        $link->addItem(new \Ease\Html\ImgTag($image, $title, ['class' => 'img-fluid mb-1', 'style' => 'max-height:64px']));
        $link->addItem(new \Ease\Html\DivTag($title, ['class' => 'small text-muted']));
        return $link;
    }
}
