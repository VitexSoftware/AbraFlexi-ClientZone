<?php

declare(strict_types=1);

namespace ClientZone;

/**
 * Odhlašovací stránka.
 *
 * @author    Vitex <vitex@hippy.cz>
 * @copyright Vitex@hippy.cz (G) 2009-2026
 */
require_once 'includes/Init.php';

$oUser->logout();
$oUser = \Ease\Shared::user(new \Ease\Anonym());
$oUser->addStatusMessage(_('You have been signed out'), 'success');

$oPage->addToHeader(new ui\PageTop(_('Sign Off')));

$wrap = $oPage->container->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-auth']));
$card = $wrap->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-auth-card']));
$card->addItem(new \Ease\Html\H1Tag(_('Signed out')));
$card->addItem(new \Ease\Html\PTag(
    _('Thank you for your favor and we look forward to another visit'),
    ['class' => 'lead'],
));
$card->addItem(new \Ease\Html\ATag(
    'login.php',
    _('Sign In').' <span class="arrow">→</span>',
    ['class' => 'btn btn-glow'],
));

$oPage->addItem(new ui\PageBottom());
$oPage->draw();
