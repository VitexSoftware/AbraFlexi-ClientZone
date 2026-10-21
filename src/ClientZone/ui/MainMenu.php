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

        if ($user instanceof \ClientZone\Customer && $user->isLogged()) {
            $nav->addMenuItem(new NavBarSearchBox('search', 'search.php'));
            $ordersMenu = [
                'orderform.php' => '<i class="fa fa-plus"></i> '._('New order'),
                'myorders.php' => '<i class="fa fa-list"></i> '._('My orders'),
                'myinvoices.php' => '<i class="fa fa-file-invoice"></i> '._('My invoices'),
            ];
            if ($user->isSupplierCustomer()) {
                $ordersMenu['myreceivedinvoices.php'] = '<i class="fa fa-file-invoice-dollar"></i> '
                    ._('Received invoices');
            }
            $nav->addDropDownMenu(_('Orders'), $ordersMenu);
            $nav->addMenuItem(
                $this->cartLink($cartCount),
                true,
                'right',
            );
            $nav->addDropDownMenu(
                '<i class="fa fa-user"></i> '.$user->getUserLogin(),
                [
                    'dashboard.php' => '<i class="fa fa-home"></i> '._('Dashboard'),
                    'myaccount.php' => '<i class="fa fa-info"></i> '._('My Account'),
                    'mycontact.php' => '<i class="fa fa-user"></i> '._('My Contact'),
                    'changepassword.php' => '<i class="fa fa-lock"></i> '._('Password change'),
                    'logout.php' => '<i class="fa fa-power-off"></i> '._('Sign Out'),
                ],
                'right',
            );

            return;
        }

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
