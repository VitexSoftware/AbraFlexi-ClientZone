<?php

namespace ClientZone;

/**
 * clientzone - Application Setup.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2019 VitexSoftware v.s.cz
 */
require_once 'includes/Init.php';

if (!empty($shared->getConfigValue('CONFIGURED'))) {
    $oPage->redirect('index.php');
    $oPage->draw();
    exit;
}


$cfg = new Configurator('../clientzone.json', $shared->configuration);
if ($oPage->isPosted()) {
    $cfg->takeData($cfg->processForm($_POST));
    $cfg->publish();
    $configSaved = $cfg->saveConfig();
} else {
    if ($oPage->getRequestValue('CONFIGURED') == 'true') {
        $cfg->setDataValue('CONFIGURED', true);
        $configSaved = $cfg->saveConfig();
        $cfg->addStatusMessage(_('Configuration Finished'),
            $configSaved ? 'success' : 'error' );
        $oPage->redirect('index.php');
    }
    $configSaved = false;
}


$cfgForm = new ui\SetupForm();
$cfgForm->fillUp($cfg->getData());

$oPage->addToHeader(new ui\PageTop(_('ClientZone Setup')));



$setupRow = new \Ease\TWB5\Row();
$setupRow->addColumn(4, $cfgForm);

if (!empty($shared->getConfigValue('ABRAFLEXI_URL')) &&
    !empty($shared->getConfigValue('ABRAFLEXI_LOGIN')) &&
    !empty($shared->getConfigValue('ABRAFLEXI_PASSWORD'))
) {

    try {
        $statuser = new \AbraFlexi\ui\TWB5\StatusInfoBox();
    } catch (\AbraFlexi\Exception $e) {
        $oPage->addStatusMessage($e->getMessage(), 'error');
        $statuser = null;
    }

    $statusBlock = new \Ease\Html\DivTag($statuser);

    if ($statuser && ($statuser->lastResponseCode == 200) && $configSaved) {

        $oPage->addCss('.glow {
  font-size: 50px;
  color: #fff;
  text-align: center;
  -webkit-animation: glow 1s ease-in-out infinite alternate;
  -moz-animation: glow 1s ease-in-out infinite alternate;
  animation: glow 1s ease-in-out infinite alternate;
}

@-webkit-keyframes glow {
  from {
    text-shadow: 0 0 10px #fff, 0 0 20px #fff, 0 0 30px #000073, 0 0 40px #000073, 0 0 50px #000073, 0 0 60px #000073, 0 0 70px #000073;
  }
  to {
    text-shadow: 0 0 20px #fff, 0 0 30px #ff4da6, 0 0 40px #ff4da6, 0 0 50px #ff4da6, 0 0 60px #ff4da6, 0 0 70px #ff4da6, 0 0 80px #ff4da6;
  }
}');

        $statusBlock->addItem(new \Ease\TWB5\LinkButton('?CONFIGURED=true',
                _('Finish Configuration'), 'success btn-lg glow'));
    }

    $statusBlock->addItem(new \Ease\Html\FieldSet(_('App Config File Preview'),
            new \Ease\Html\PreTag($cfg->getJson($cfg->getAppData()))));

    $statusBlock->addItem(new \Ease\Html\FieldSet(_('FlexiBee Config File Preview'),
            new \Ease\Html\PreTag($cfg->getJson($cfg->getAbraFlexiData()))));


    $setupRow->addColumn(4,
        new \Ease\Html\FieldSet(_('AbraFlexi Connection Status'), $statusBlock));
} else {
    $setupRow->addColumn(4,
        new \Ease\Html\FieldSet(_('AbraFlexi Connection Status'),
            new \Ease\TWB5\LinkButton(null, _('Connection not set'), 'warning')));
}



$oPage->container->addItem(new \Ease\TWB5\Panel(_('AbraFlexi Setup'), 'warning',
        $setupRow));


$oPage->addItem(new ui\PageBottom());

$oPage->draw();
