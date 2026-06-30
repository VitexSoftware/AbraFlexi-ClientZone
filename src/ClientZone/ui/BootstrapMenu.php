<?php

declare(strict_types=1);

/**
 * clientzone - BootstrapMenu.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2024 VitexSoftware v.s.cz
 */

namespace ClientZone\ui;

class BootstrapMenu extends \Ease\TWB5\Navbar
{
    public function __construct()
    {
        $this->mainpage = 'index.php';
        parent::__construct(
            [
                new \Ease\Html\ImgTag(
                    'images/clientzone-logo.svg',
                    \Ease\Shared::appName(),
                    ['height' => 32, 'width' => 32, 'class' => 'me-2'],
                ),
                new \Ease\Html\SpanTag(\Ease\Shared::appName(), ['class' => 'fw-semibold text-white']),
            ],
            'clientzone-navbar',
            ['class' => 'cz-navbar sticky-top navbar-expand-lg navbar-dark'],
        );
    }

    public function draw(): void
    {
        $messagesBlock = \Ease\WebPage::singleton()->getStatusMessagesBlock([
            'id'    => 'StatusMessages',
            'class' => 'alert alert-dismissible fade show mb-0',
            'role'  => 'alert',
        ]);
        if (!$messagesBlock->isEmpty()) {
            $this->addItem($messagesBlock);
            \Ease\Shared::logger()->cleanMessages();
        }
        parent::draw();
    }
}
