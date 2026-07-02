<?php

declare(strict_types=1);

/**
 * clientzone - Hlavní menu.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2024 VitexSoftware v.s.cz
 */

namespace ClientZone\ui;

class MainMenu extends \Ease\Html\DivTag
{
    public function __construct()
    {
        parent::__construct(null, ['id' => 'MainMenu']);
    }

    public function afterAdd(): void
    {
        if (!empty($this->pageParts)) {
            return;
        }

        $nav  = $this->addItem(new BootstrapMenu());
        $user = \Ease\Shared::user();

        switch (get_class($user)) {
            case 'ClientZone\Customer':
                $nav->addMenuItem(new NavBarSearchBox('search', 'search.php'));
                $nav->addDropDownMenu(
                    '<img width="22" src="images/order.svg" class="me-1"> '._('Orders'),
                    [
                        'orderform.php' => '<i class="fa fa-plus"></i> '._('New order'),
                        'myorders.php'  => '<i class="fa fa-list"></i> '._('My orders'),
                    ],
                );
                if (isset($_SESSION['cart']) && count($_SESSION['cart'])) {
                    $nav->addMenuItem(
                        new \Ease\Html\ATag(
                            'cart.php',
                            '<img width="22" src="images/cart.svg" class="me-1"> '._('Cart').
                            ' <span class="badge bg-warning text-dark">'.count($_SESSION['cart']).'</span>',
                        ),
                        true,
                        'right',
                    );
                }
                $nav->addDropDownMenu(
                    '<i class="fa fa-user"></i> '.$user->getUserLogin(),
                    [
                        'myaccount.php'      => '<i class="fa fa-home"></i> '._('My Account'),
                        'mycontact.php'      => '<i class="fa fa-user"></i> '._('My Contact'),
                        'changepassword.php' => '<i class="fa fa-lock"></i> '._('Password change'),
                        'logout.php'         => '<i class="fa fa-power-off"></i> '._('Sign Out'),
                    ],
                    'right',
                );
                break;

            case 'ClientZone\User':
                $nav->addDropDownMenu(
                    '<i class="fa fa-wrench"></i> '._('Admin'),
                    [
                        'settings.php'       => '<i class="fa fa-cog"></i> '._('Settings'),
                        'changepassword.php' => '<i class="fa fa-lock"></i> '._('Password change'),
                        'about.php'          => '<i class="fa fa-info-circle"></i> '._('About'),
                    ],
                );
                $nav->addMenuItem(
                    new \Ease\Html\ATag('logout.php', '<i class="fa fa-power-off"></i> '._('Sign Out')),
                    true,
                    'right',
                );
                break;

            default: // Anonym
                if (isset($_SESSION['cart']) && count($_SESSION['cart'])) {
                    $nav->addMenuItem(
                        new \Ease\Html\ATag(
                            'cart.php',
                            '<img width="22" src="images/cart.svg" class="me-1"> '._('Cart').
                            ' <span class="badge bg-warning text-dark">'.count($_SESSION['cart']).'</span>',
                        ),
                        true,
                        'right',
                    );
                }
                if (\Ease\Shared::cfg('ALLOW_REGISTER') === 'true') {
                    $nav->addMenuItem(
                        new \Ease\Html\ATag('newcustomer.php', '<i class="fa fa-user-plus"></i> '._('Sign Up')),
                        true,
                        'right',
                    );
                }
                $nav->addMenuItem(
                    new \Ease\Html\ATag('login.php', '<i class="fa fa-sign-in"></i> '._('Sign In')),
                    true,
                    'right',
                );
                break;
        }
    }

    public function finalize(): void
    {
        parent::finalize();
    }
}
