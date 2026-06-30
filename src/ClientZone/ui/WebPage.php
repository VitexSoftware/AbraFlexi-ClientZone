<?php

declare(strict_types=1);

/**
 * clientzone - WebPage.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2024 VitexSoftware v.s.cz
 */

namespace ClientZone\ui;

class WebPage extends \Ease\TWB5\WebPage
{
    public string $bootstrapCSS = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css';
    public string $jqueryJavaScript = 'https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js';

    public \Ease\TWB5\Container $container;
    public ?\Ease\Html\DivTag $columnI = null;
    public ?\Ease\Html\DivTag $columnII = null;
    public ?\Ease\Html\DivTag $columnIII = null;

    public function __construct(?string $pageTitle = null, mixed &$userObject = null)
    {
        if (is_null($userObject)) {
            $userObject = \Ease\Shared::user();
        }
        parent::__construct((string) $pageTitle);

        $this->head->addItem('<link rel="shortcut icon" type="image/svg+xml" href="images/logo.png">');
        $this->head->addItem('<link rel="apple-touch-icon-precomposed" type="image/svg+xml" href="images/logo.png">');
        $this->head->addItem('<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">');
        $this->head->addItem('<link rel="stylesheet" href="/javascript/font-awesome/css/font-awesome.min.css">');

        $this->includeCSS('css/default.css');

        $this->addCSS(<<<'CSS'
            body { background-color: #f0f2f5; color: #343a40; font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
            .card { transition: all 0.3s ease; border-radius: 8px; border: none; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
            .card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.13) !important; }
            .btn { border-radius: 6px; font-weight: 500; transition: all 0.2s; }
            .navbar { box-shadow: 0 2px 8px rgba(0,0,0,0.10); }
            .nav-link { font-weight: 500; }
            #StatusMessages { margin: 4px; padding: 4px; overflow-y: auto; max-height: 200px; }
            #StatusMessages .alert { margin: 2px; padding: 4px 8px; }
            .table { border-radius: 8px; overflow: hidden; }
        CSS);

        $this->container = $this->addItem(new \Ease\TWB5\Container());
        $this->container->setTagClass('container-fluid py-3');
    }

    public function addPageColumns(): void
    {
        $row = $this->container->addItem(new \Ease\Html\DivTag(null, ['class' => 'row g-3']));
        $this->columnI   = $row->addItem(new \Ease\Html\DivTag(null, ['class' => 'col-md-4']));
        $this->columnII  = $row->addItem(new \Ease\Html\DivTag(null, ['class' => 'col-md-4']));
        $this->columnIII = $row->addItem(new \Ease\Html\DivTag(null, ['class' => 'col-md-4']));
    }

    public function onlyForAdmin(string $loginPage = 'login.php'): void
    {
        $user = \Ease\Shared::user();
        if (!$user->getSettingValue('admin')) {
            $user->addStatusMessage(\gettext('Please sign in as admin first'), 'warning');
            $this->redirect($loginPage);
            exit;
        }
    }

    public function onlyForUser(string $loginPage = 'login.php'): void
    {
        $user = \Ease\Shared::user();
        if (get_class($user) !== 'ClientZone\User') {
            $this->addStatusMessage(\gettext('Please sign in as user first'), 'warning');
            $this->redirect($loginPage);
            exit;
        }
    }

    public function onlyForLogged($loginPage = 'login.php', $message = null)
    {
        return parent::onlyForLogged($loginPage.'?backurl='.urlencode($_SERVER['REQUEST_URI']), $message);
    }
}
