<?php

declare(strict_types=1);

/**
 * clientzone - Shop homepage hero.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2026 VitexSoftware v.s.cz
 */

namespace ClientZone\ui;

/**
 * Full-bleed intro for the e-shop front page.
 */
class ShopHero extends \Ease\Html\DivTag
{
    public function __construct()
    {
        parent::__construct(null, ['class' => 'page-hero cz-shop-hero']);

        $inner = $this->addItem(new \Ease\Html\DivTag(null, ['class' => 'page-hero-inner']));
        $inner->addItem(new \Ease\Html\DivTag(_('Self-service portal'), ['class' => 'eyebrow']));
        $inner->addItem(new \Ease\Html\H1Tag(
            new \Ease\Html\SpanTag(\Ease\Shared::appName(), ['class' => 'grad']),
        ));
        $inner->addItem(new \Ease\Html\PTag(
            _('Order domains, VoIP credit and hosting services online — instantly, without waiting for a callback.'),
            ['class' => 'lead'],
        ));

        $actions = $inner->addItem(new \Ease\Html\DivTag(null, ['class' => 'page-hero-actions']));
        $actions->addItem(new \Ease\Html\ATag(
            '#catalog',
            _('Browse catalog').' <span class="arrow">→</span>',
            ['class' => 'btn btn-glow'],
        ));

        $user = \Ease\Shared::user();
        if (get_class($user) === 'Ease\Anonym') {
            $actions->addItem(new \Ease\Html\ATag(
                'login.php',
                _('Sign In'),
                ['class' => 'btn btn-line'],
            ));
        } else {
            $actions->addItem(new \Ease\Html\ATag(
                'orderform.php',
                _('New order'),
                ['class' => 'btn btn-line'],
            ));
        }
    }
}
