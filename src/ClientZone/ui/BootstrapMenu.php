<?php

declare(strict_types=1);

/**
 * clientzone - BootstrapMenu.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2026 VitexSoftware v.s.cz
 */

namespace ClientZone\ui;

use Ease\Html\DivTag;

class BootstrapMenu extends \Ease\TWB5\Navbar
{
    /**
     * Collapse / toggler target id (parent keeps $navBarName private).
     */
    private const NAVBAR_ID = 'clientzone-navbar';

    public function __construct()
    {
        $user = \Ease\Shared::user();
        $this->mainpage = ($user instanceof \ClientZone\Customer) ? 'dashboard.php' : 'index.php';
        $appName = (string) (\Ease\Shared::appName() ?: _('Client Zone'));
        $parts = preg_split('/\s+/u', $appName, 2) ?: [$appName];
        $brand = '<b>'.htmlspecialchars($parts[0]).'</b>'
            .(isset($parts[1]) ? ' '.htmlspecialchars($parts[1]) : '');
        parent::__construct(
            '<img src="images/clientzone-logo.svg" alt="" width="36" height="36" class="brand-logo">'
            .'<span class="brand-name">'.$brand.'</span>',
            self::NAVBAR_ID,
            ['class' => 'site-header navbar-expand-lg fixed-top'],
        );
    }

    /**
     * Ease TWB5 Navbar::navBarCollapse() only embeds leftContent — right-side
     * items (cart, account) would never render. Include both sides.
     * Sign Out lives in the customer account dropdown (MainMenu), not here.
     */
    public function navBarCollapse()
    {
        $collapse = new DivTag(
            [$this->leftContent, $this->rightContent],
            ['class' => 'collapse navbar-collapse', 'id' => self::NAVBAR_ID],
        );

        $controls = new DivTag(null, ['class' => 'header-controls']);
        $controls->addItem(
            '<button type="button" class="icon-btn theme-toggle" aria-label="'
            .htmlspecialchars(_('Switch light / dark mode'))
            .'" title="'.htmlspecialchars(_('Switch light / dark mode')).'">'
            .'<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
            .'stroke-width="1.6" stroke-linecap="round" aria-hidden="true">'
            .'<circle cx="12" cy="12" r="4.2"/>'
            .'<path d="M12 2.5v2.2M12 19.3v2.2M4.6 4.6l1.6 1.6M17.8 17.8l1.6 1.6'
            .'M2.5 12h2.2M19.3 12h2.2M4.6 19.4l1.6-1.6M17.8 6.2l1.6-1.6"/></svg></button>',
        );
        $collapse->addItem($controls);

        return $collapse;
    }

    /**
     * NavItemDropDown is already an &lt;li&gt; — do not wrap it again.
     *
     * @param mixed  $content
     * @param bool   $enabled
     * @param string $placement
     */
    public function addMenuItem($content, $enabled = true, $placement = 'left')
    {
        if ($content instanceof \Ease\TWB5\NavItemDropDown) {
            return $placement === 'left'
                ? $this->leftContent->addItem($content)
                : $this->rightContent->addItem($content);
        }

        return parent::addMenuItem($content, $enabled, $placement);
    }

    /**
     * Hamburger must toggle the collapsed menu.
     */
    public function navBarToggler()
    {
        $toggler = parent::navBarToggler();
        $toggler->setTagProperties(['data-bs-toggle' => 'collapse']);

        return $toggler;
    }

    public function draw(): void
    {
        $messagesBlock = \Ease\WebPage::singleton()->getStatusMessagesBlock([
            'id'    => 'status-messages',
            'title' => _('Click to hide messages'),
        ]);
        if (!$messagesBlock->isEmpty()) {
            $this->addItem($messagesBlock);
            \Ease\Shared::logger()->cleanMessages();
        }
        parent::draw();
    }
}
