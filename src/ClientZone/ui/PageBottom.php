<?php

declare(strict_types=1);

/**
 * clientzone - Spodek Stránky Webu.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2026 VitexSoftware v.s.cz
 */

namespace ClientZone\ui;

class PageBottom extends \Ease\Html\FooterTag
{
    public function __construct($content = null)
    {
        $version = (defined('APP_VERSION') && APP_VERSION !== '' && APP_VERSION !== '0.0.0')
            ? APP_VERSION
            : 'dev';
        // Package layout: .../abraflexi-clientzone/ClientZone/ui → app root is 2 levels up.
        // Dev layout: src/ClientZone/ui → src/ has no composer.json; repo root is one level above src.
        $appRoot = \dirname(__DIR__, 2);
        $composerCandidates = [$appRoot.'/composer.json'];
        if (str_ends_with($appRoot, '/src')) {
            $composerCandidates[] = \dirname($appRoot).'/composer.json';
        }
        foreach ($composerCandidates as $composer) {
            if (!is_readable($composer)) {
                continue;
            }
            $decoded = json_decode((string) file_get_contents($composer));
            if (is_object($decoded) && !empty($decoded->version)) {
                $version = (string) $decoded->version;
            }
            break;
        }

        parent::__construct($content);
        $this->setTagID('footer');
        $this->addTagClass('footer');

        $appName = (string) (\Ease\Shared::appName() ?: _('Client Zone'));
        $parts = preg_split('/\s+/u', $appName, 2) ?: [$appName];
        $brand = '<b>'.htmlspecialchars($parts[0]).'</b>'
            .(isset($parts[1]) ? ' '.htmlspecialchars($parts[1]) : '');
        $copyright = _('&copy; 2017-2026 Vitex Software');
        $versionLabel = htmlspecialchars(_('Version').': '.$version);

        $columns = [
            _('Products') => [
                'https://github.com/VitexSoftware/FlexiProxy' => 'FlexiProXY',
                'https://github.com/VitexSoftware/Flexplorer/' => 'FlexPlorer',
                'https://multiflexi.vitexsoftware.com/' => 'MultiFlexi',
            ],
            _('Hosted by') => [
                'https://spoje.net/' => 'Spoje.Net',
            ],
            _('More') => [
                'about.php' => _('About'),
                'https://github.com/VitexSoftware/AbraFlexi-ClientZone/' => _('Source code'),
                'https://github.com/VitexSoftware/AbraFlexi-ClientZone/issues' => _('Issues'),
            ],
        ];

        $cols = '';
        foreach ($columns as $heading => $links) {
            $cols .= '<div><h4>'.$heading.'</h4><ul>';
            foreach ($links as $url => $label) {
                $cols .= '<li><a href="'.htmlspecialchars((string) $url).'">'.$label.'</a></li>';
            }
            $cols .= '</ul></div>';
        }

        $this->addItem(<<<HTML
<svg class="topo" viewBox="0 0 1440 200" preserveAspectRatio="none" aria-hidden="true"><g fill="none" stroke="currentColor" stroke-width="1"><path d="M0 150C200 120 320 170 520 140S860 90 1060 130 1340 170 1440 140"/><path d="M0 170C220 145 330 190 540 160S880 115 1080 150 1350 190 1440 165"/><path d="M0 125C190 95 310 145 500 118S840 65 1040 105 1330 145 1440 115"/><path d="M0 100C180 72 300 120 480 95S820 42 1020 82 1320 120 1440 92"/></g></svg>
<div class="foot-wrap">
  <div class="foot-top">
    <a class="brand" href="index.php"><img src="images/clientzone-logo.svg" alt="" width="36" height="36"><span class="brand-name">{$brand}</span></a>
    <span class="motto hand">{$versionLabel}</span>
    <div class="social">
      <a class="icon-btn" href="https://github.com/VitexSoftware/AbraFlexi-ClientZone/" aria-label="GitHub"><i class="fa-brands fa-github"></i></a>
      <a class="icon-btn" href="https://vitexsoftware.cz/" aria-label="Vitex Software"><i class="fa-solid fa-globe"></i></a>
      <a class="icon-btn" href="https://spoje.net/" aria-label="Spoje.Net"><i class="fa-solid fa-network-wired"></i></a>
    </div>
  </div>
  <div class="foot-cols" style="grid-template-columns: repeat(3, 1fr)">{$cols}</div>
  <div class="foot-bottom">
    <span>{$copyright}</span>
    <a href="https://vitexsoftware.cz/">Vitex Software</a>
  </div>
</div>
HTML);
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
    }
}
