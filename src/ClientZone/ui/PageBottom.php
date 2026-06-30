<?php
/**
 * clientzone - Spodek Stránky Webu.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017 VitexSoftware v.s.cz
 */

namespace ClientZone\ui;

class PageBottom extends \Ease\Html\FooterTag
{

    public function __construct($content = null)
    {
        $composer = '../composer.json';
        if (!file_exists($composer)) {
            $composer = '/usr/share/clientzone/composer.json';
        }
        $appInfo = json_decode(file_get_contents($composer));

        parent::__construct($content);
        $this->SetTagID('footer');
        $this->addItem('<hr>');

        $rowFluid1 = new \Ease\TWB5\Row();
        $colA      = $rowFluid1->addItem(new \Ease\TWB5\Col(1));
        $colD      = $rowFluid1->addItem(new \Ease\TWB5\Col(4));
        $colD->addItem(new \Ease\Html\DivTag(_('Other products')));

        $icorow = new \Ease\TWB5\Row();

        $icorow->addColumn(6,
            new \Ease\Html\ATag('https://github.com/VitexSoftware/FlexiProxy',
            new \Ease\Html\ImgTag('images/flexiproxy-logo.png', 'FlexiProXY',
            ['class' => 'img-responsive', 'style' => 'height: 100px;'])));
        $icorow->addColumn(6,
            new \Ease\Html\ATag('https://github.com/VitexSoftware/Flexplorer/',
            new \Ease\Html\ImgTag('images/flexplorer-logo.png', 'FlexPlorer',
            ['class' => 'img-responsive', 'style' => 'height: 100px;'])));

        $colD->addItem($icorow);

        $colE   = $rowFluid1->addItem(new \Ease\TWB5\Col(2));
        $listE1 = $colE->addItem(new \Ease\Html\UlTag(_('Hosted by'),
            ['style' => 'list-style-type: none']));
        $listE1->addItemSmart(new \Ease\Html\ATag('https://spoje.net/',
            new \Ease\Html\ImgTag('images/spoje-net_logo.gif', 'Spoje.Net',
            ['class' => 'img-responsive'])));

        $colF   = $rowFluid1->addItem(new \Ease\TWB5\Col(2));
        $listF1 = $colF->addItem(new \Ease\Html\UlTag(_('More'),
            ['style' => 'list-style-type: none']));
        $listF1->addItemSmart(new \Ease\Html\ATag('about.php', _('About')));
        $listF1->addItemSmart(new \Ease\Html\ATag('https://github.com/VitexSoftware/ClientZone/',
            _('Source code')));
        $listF1->addItemSmart(new \Ease\Html\ATag('https://github.com/VitexSoftware/ClientZone/issues',
            _('Issues')));

        $colG = $rowFluid1->addItem(new \Ease\TWB5\Col(2));
        $colG->addItem(_('Version').': '.$appInfo->version.' '.'&copy; 2017-2019 <a href="https://vitexsoftware.cz/">Vitex Software</a>');

        $this->addItem($rowFluid1);
    }

    /**
     * Zobrazí přehled právě přihlášených a spodek stránky.
     */
    public function finalize(): void
    {
        parent::finalize();
        if (isset($this->webPage->heroUnit) && !count($this->webPage->heroUnit->pageParts)) {
            unset($this->webPage->container->pageParts['\Ease\Html\DivTag@heroUnit']);
        }

        $this->includeCss('/javascript/font-awesome/css/font-awesome.min.css');
    }
}
