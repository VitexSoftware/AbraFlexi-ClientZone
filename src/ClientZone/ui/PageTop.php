<?php

declare(strict_types=1);

/**
 * clientzone - Vršek stránky.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2026 VitexSoftware v.s.cz
 */

namespace ClientZone\ui;

/**
 * Page TOP.
 */
class PageTop extends \Ease\Html\DivTag
{
    public $pageTitle = 'Page Heading';

    /**
     * @param null|string $pageTitle
     */
    public function __construct($pageTitle = null)
    {
        parent::__construct();
        if ($pageTitle !== null && $pageTitle !== '') {
            \Ease\WebPage::singleton()->setPageTitle((string) $pageTitle);
        }
    }

    /**
     * Vloží vršek stránky a hlavní menu.
     */
    public function finalize(): void
    {
        parent::finalize();
        $this->addItem(new MainMenu());
    }
}
