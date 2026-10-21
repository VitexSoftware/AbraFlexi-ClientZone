<?php

declare(strict_types=1);

/**
 * clientzone - Hlavní menu.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2026 VitexSoftware v.s.cz
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

        $nav = $this->addItem(new BootstrapMenu());
        $user = \Ease\Shared::user();
        $cartCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;

        $nav->addMenuItem(new \Ease\Html\ATag('index.php#catalog', _('Catalog')));

        switch (get_class($user)) {
            case 'ClientZone\Customer':
                $nav->addMenuItem(new NavBarSearchBox('search', 'search.php'));
                $nav->addDropDownMenu(
                    _('Orders'),
                    [
                        'orderform.php' => '<i class="fa fa-plus"></i> '._('New order'),
                        'myorders.php' => '<i class="fa fa-list"></i> '._('My orders'),
                        'myinvoices.php' => '<i class="fa fa-file-invoice"></i> '._('My invoices'),
                    ],
                );
                $nav->addMenuItem(
                    $this->cartLink($cartCount),
                    true,
                    'right',
                );
                $nav->addDropDownMenu(
                    '<i class="fa fa-user"></i> '.$user->getUserLogin(),
                    [
                        'myaccount.php' => '<i class="fa fa-home"></i> '._('My Account'),
                        'mycontact.php' => '<i class="fa fa-user"></i> '._('My Contact'),
                        'changepassword.php' => '<i class="fa fa-lock"></i> '._('Password change'),
                        'logout.php' => '<i class="fa fa-power-off"></i> '._('Sign Out'),
                    ],
                    'right',
                );
                break;

            case 'ClientZone\User':
                $nav->addDropDownMenu(
                    '<i class="fa fa-wrench"></i> '._('Admin'),
                    [
                        'settings.php' => '<i class="fa fa-cog"></i> '._('Settings'),
                        'adminpricelist.php' => '<i class="fa fa-tags"></i> '._('Pricelist'),
                        'changepassword.php' => '<i class="fa fa-lock"></i> '._('Password change'),
                        'about.php' => '<i class="fa fa-info-circle"></i> '._('About'),
                    ],
                );
                $nav->addMenuItem(
                    new \Ease\Html\ATag('logout.php', '<i class="fa fa-power-off"></i> '._('Sign Out')),
                    true,
                    'right',
                );
                break;

            default:
                $nav->addMenuItem(
                    $this->cartLink($cartCount),
                    true,
                    'right',
                );
                if (\Ease\Shared::cfg('ALLOW_REGISTER') === 'true') {
                    $nav->addMenuItem(
                        new \Ease\Html\ATag('newcustomer.php', _('Sign Up')),
                        true,
                        'right',
                    );
                }
                $nav->addMenuItem(
                    new \Ease\Html\ATag('login.php', _('Sign In'), ['class' => 'btn btn-glow btn-sm ms-2']),
                    true,
                    'right',
                );
                break;
        }
    }

    private function cartLink(int $cartCount): \Ease\Html\ATag
    {
        $badge = $cartCount > 0
            ? ' <span class="cz-cart-badge">'.$cartCount.'</span>'
            : '';

        return new \Ease\Html\ATag(
            'cart.php',
            '<i class="fa fa-shopping-cart" aria-hidden="true"></i> '._('Cart').$badge,
            ['class' => 'cz-nav-cart'],
        );
    }
}
