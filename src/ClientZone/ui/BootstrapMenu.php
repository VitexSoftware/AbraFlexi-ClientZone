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
    public function __construct($name = null, $content = null, $properties = null)
    {
        $brand = new \Ease\Html\ATag('index.php', [
            new \Ease\Html\ImgTag(
                'images/clientzone-logo.svg',
                \Ease\Shared::appName(),
                ['height' => 32, 'width' => 32, 'class' => 'me-2']
            ),
            new \Ease\Html\SpanTag(\Ease\Shared::appName(), ['class' => 'fw-semibold text-white']),
        ], ['class' => 'navbar-brand d-flex align-items-center text-decoration-none']);

        parent::__construct($brand, 'clientzone-navbar', ['class' => 'cz-navbar sticky-top']);

        $user = \Ease\Shared::user();

        switch (get_class($user)) {
            case 'ClientZone\User':
                $this->addDropDownMenu(
                    '<i class="fa fa-wrench"></i> '._('Admin'),
                    [
                        'settings.php'       => '<i class="fa fa-cog"></i> '._('Settings'),
                        'changepassword.php' => '<i class="fa fa-lock"></i> '._('Password change'),
                        'about.php'          => '<i class="fa fa-info-circle"></i> '._('About'),
                    ]
                );
                $this->addMenuItem(
                    new \Ease\Html\ATag('logout.php', '<i class="fa fa-power-off"></i> '._('Sign Out')),
                    true,
                    'right'
                );
                break;

            case 'ClientZone\Customer':
                $this->addDropDownMenu(
                    '<img width=22 src=images/order.svg class="me-1"> '._('Orders'),
                    [
                        'orderform.php' => '<i class="fa fa-plus"></i> '._('New order'),
                        'myorders.php'  => '<i class="fa fa-list"></i> '._('My orders'),
                    ]
                );

                if (isset($_SESSION['cart']) && count($_SESSION['cart'])) {
                    $this->addMenuItem(
                        new \Ease\Html\ATag('cart.php',
                            '<img width=22 src=images/cart.svg class="me-1"> '.
                            _('Cart').' <span class="badge bg-warning text-dark">'.count($_SESSION['cart']).'</span>'
                        ),
                        true,
                        'right'
                    );
                }

                $this->addDropDownMenu(
                    '<i class="fa fa-user"></i> '.$user->getUserLogin(),
                    [
                        'myaccount.php'      => '<i class="fa fa-home"></i> '._('My Account'),
                        'mycontact.php'      => '<i class="fa fa-user"></i> '._('My Contact'),
                        'changepassword.php' => '<i class="fa fa-lock"></i> '._('Password change'),
                        'logout.php'         => '<i class="fa fa-power-off"></i> '._('Sign Out'),
                    ],
                    'right'
                );
                break;

            default: // Anonym
                if (isset($_SESSION['cart']) && count($_SESSION['cart'])) {
                    $this->addMenuItem(
                        new \Ease\Html\ATag('cart.php',
                            '<img width=22 src=images/cart.svg class="me-1"> '.
                            _('Cart').' <span class="badge bg-warning text-dark">'.count($_SESSION['cart']).'</span>'
                        ),
                        true,
                        'right'
                    );
                }
                $this->addDropDownMenu(
                    '<i class="fa fa-sign-in"></i> '._('Sign In'),
                    ['login.php' => '<i class="fa fa-lock"></i> '._('Login')],
                    'right'
                );
                break;
        }
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
