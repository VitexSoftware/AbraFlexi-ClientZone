<?php

namespace ClientZone\ui;

/**
 * Description of ZewlScoreLabel
 *
 * @author vitex
 */
class ZewlScoreLabel extends \Ease\TWB5\Badge
{

    public function __construct($address)
    {
        $engine    = new \AbraFlexi\Bricks\Upominac();
        $zewlScore = $engine->getCustomerScore($address->getMyKey());

        switch ($zewlScore) {
            case 0:
                $type = 'success';
                break;
            case 1:
                $type = 'info';
                break;
            case 2:
                $type = 'warning';
                break;
            case 3:
                $type = 'danger';
                break;
        }

        parent::__construct($type, $zewlScore, ['title' => _('Zewl score')]);
    }
}
