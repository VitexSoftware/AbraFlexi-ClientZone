<?php

declare(strict_types=1);

/**
 * clientzone - WebPage.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2026 VitexSoftware v.s.cz
 */

namespace ClientZone\ui;

class WebPage extends \Ease\TWB5\WebPage
{
    /**
     * Bump when css/vitex.css or js/vitex.js change, so browsers fetch the new version.
     */
    public const ASSET_VERSION = '1.1.1';

    public string $bootstrapCSS = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css';
    public string $jqueryJavaScript = 'https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js';
    public string $bootstrapThemeCSS = '';

    public \Ease\TWB5\Container $container;
    public ?\Ease\Html\DivTag $columnI = null;
    public ?\Ease\Html\DivTag $columnII = null;
    public ?\Ease\Html\DivTag $columnIII = null;

    public function __construct(?string $pageTitle = null, mixed &$userObject = null)
    {
        if (is_null($userObject)) {
            $userObject = \Ease\Shared::user();
        }
        if ($pageTitle === null || $pageTitle === '') {
            $pageTitle = \Ease\Shared::appName() ?: _('Client Zone');
        }
        parent::__construct((string) $pageTitle);

        // Light/dark mode before first paint (dark is the default — same as v.s.cz).
        $this->head->addItem('<script>(function(){var t="dark";try{t=localStorage.getItem("vsTheme")||t}catch(e){}var d=document.documentElement;d.classList.add("js");d.setAttribute("data-theme",t);d.setAttribute("data-bs-theme",t)})()</script>');
        $this->head->addItem('<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>');
        $this->includeCss('https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600&family=Caveat:wght@400;500&display=swap');
        $this->includeCss('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css');
        $this->includeCss('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/v4-shims.min.css');
        $this->includeCss('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css');

        $this->includeCSS('css/default.css');
        $this->includeCss('css/vitex.css?v='.self::ASSET_VERSION);
        $this->includeJavaScript('js/vitex.js?v='.self::ASSET_VERSION);

        $this->head->addItem('<link rel="shortcut icon" type="image/svg+xml" href="images/logo.png">');
        $this->head->addItem('<link rel="apple-touch-icon-precomposed" type="image/svg+xml" href="images/logo.png">');
        $this->head->addItem('<meta name="theme-color" content="#1d1440">');

        $this->body->setTagID('page-top');
        $this->container = $this->addItem(new \Ease\TWB5\Container());
        $this->container->setTagClass('container-fluid page-content');
    }

    public function addPageColumns(): void
    {
        $row = $this->container->addItem(new \Ease\Html\DivTag(null, ['class' => 'row g-3']));
        $this->columnI   = $row->addItem(new \Ease\Html\DivTag(null, ['class' => 'col-md-4']));
        $this->columnII  = $row->addItem(new \Ease\Html\DivTag(null, ['class' => 'col-md-4']));
        $this->columnIII = $row->addItem(new \Ease\Html\DivTag(null, ['class' => 'col-md-4']));
    }

    /**
     * Require a logged-in AbraFlexi customer (no local admin role exists).
     */
    public function onlyForCustomer(string $loginPage = 'login.php', ?string $message = null): void
    {
        $this->onlyForLogged($loginPage, $message);
        $user = \Ease\Shared::user();
        if (!$user instanceof \ClientZone\Customer) {
            $this->addStatusMessage(
                $message ?? _('Please sign in with your customer account'),
                'warning',
            );
            $this->redirect($loginPage.'?backurl='.urlencode($_SERVER['REQUEST_URI'] ?? 'index.php'));
            exit;
        }
    }

    /**
     * Former admin-only tools are disabled — configuration is via .env / AbraFlexi.
     */
    public function refusePrivilegedTool(string $redirect = 'index.php'): void
    {
        $this->addStatusMessage(
            _('This tool is not available in ClientZone. Use AbraFlexi or server configuration instead.'),
            'warning',
        );
        $this->redirect($redirect);
        exit;
    }

    public function onlyForLogged($loginPage = 'login.php', $message = null)
    {
        return parent::onlyForLogged($loginPage.'?backurl='.urlencode($_SERVER['REQUEST_URI']), $message);
    }
}
