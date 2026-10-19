<?php

declare(strict_types=1);

/**
 * clientzone - Compact quick-action strip for the shop home.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2026 VitexSoftware v.s.cz
 */

namespace ClientZone\ui;

class MainPageMenu extends \Ease\Html\DivTag
{
    public \Ease\Html\DivTag $row;

    public function __construct(?string $heading = null)
    {
        parent::__construct(null, ['class' => 'cz-quick-actions']);
        if ($heading !== null && $heading !== '') {
            $this->addItem(new \Ease\Html\DivTag($heading, ['class' => 'eyebrow']));
        }
        $this->row = $this->addItem(new \Ease\Html\DivTag(null, [
            'class' => 'cz-quick-row',
        ]));
    }

    /**
     * @return \Ease\Html\ATag
     */
    public function addMenuItem(string $image, string $title, string $url): \Ease\Html\ATag
    {
        $link = $this->row->addItem(new \Ease\Html\ATag($url, null, ['class' => 'cz-tile']));
        $link->addItem(new \Ease\Html\ImgTag($image, $title, ['class' => 'img-fluid']));
        $link->addItem(new \Ease\Html\DivTag($title, ['class' => 'cz-tile-label']));

        return $link;
    }
}
