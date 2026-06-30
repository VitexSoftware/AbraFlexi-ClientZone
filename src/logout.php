<?php

namespace ClientZone;

/**
 * Odhlašovací stránka.
 *
 * @author    Vitex <vitex@hippy.cz>
 * @copyright Vitex@hippy.cz (G) 2009,2011
 */
require_once 'includes/Init.php';

$messagesBackup = $oUser->getStatusMessages(true);
$oUser->logout();
\Ease\Shared::user(new \Ease\Anonym());
$oUser->addStatusMessages($messagesBackup);


$oPage->addToHeader(new ui\PageTop(_('Sign Off')));

$oPage->container->addItem('<br/><br/><br/><br/>');
$oPage->container->addItem(new \Ease\Html\DivTag(new \Ease\Html\ATag('login.php',
    _('Thank you for your favor and we look forward to another visit'),
    ['class' => 'jumbotron'])));
$oPage->container->addItem('<br/><br/><br/><br/>');

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
