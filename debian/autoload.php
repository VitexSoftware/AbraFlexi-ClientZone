<?php

declare(strict_types=1);

defined('APP_NAME') || define('APP_NAME', 'vitexsoftware/abraflexi-clientzone');
defined('APP_VERSION') || define('APP_VERSION', '0.0.0');

require_once '/usr/share/php/Composer/InstalledVersions.php';
require_once '/usr/share/php/AbraFlexi/autoload.php';
require_once '/usr/share/php/AbraFlexiBricks/autoload.php';
require_once '/usr/share/php/EaseTWB5Widgets/autoload.php';
require_once '/usr/share/php/EaseTWB5WidgetsAbraFlexi/autoload.php';
require_once '/usr/share/php/EaseHtmlWidgets/autoload.php';
require_once '/usr/share/php/AbraFlexiUiDataTables/autoload.php';

if (is_readable('/usr/share/php/IPEXB2B/autoload.php')) {
    require_once '/usr/share/php/IPEXB2B/autoload.php';
}
if (is_readable('/usr/share/php/Subreg/autoload.php')) {
    require_once '/usr/share/php/Subreg/autoload.php';
}

spl_autoload_register(function (string $class): void {
    $mappings = [
        'ClientZone\\'           => '/usr/share/abraflexi-clientzone/ClientZone/',
        'ClientZone\\ui\\'       => '/usr/share/abraflexi-clientzone/ClientZone/ui/',
        'ClientZone\\orderplugins\\' => '/usr/share/abraflexi-clientzone/ClientZone/orderplugins/',
    ];
    foreach ($mappings as $prefix => $base) {
        if (str_starts_with($class, $prefix)) {
            $file = $base.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
            if (file_exists($file)) {
                require $file;
            }
            return;
        }
    }
});

(function (): void {
    $versions = [];
    foreach (\Composer\InstalledVersions::getAllRawData() as $d) {
        $versions = array_merge($versions, $d['versions'] ?? []);
    }
    $name    = 'unknown';
    $version = '0.0.0';
    $versions[$name] = [
        'pretty_version' => $version,
        'version'        => $version,
        'reference'      => null,
        'type'           => 'library',
        'install_path'   => '/usr/share/abraflexi-clientzone',
        'aliases'        => [],
        'dev_requirement' => false,
    ];
    \Composer\InstalledVersions::reload([
        'root' => [
            'name'           => $name,
            'pretty_version' => $version,
            'version'        => $version,
            'reference'      => null,
            'type'           => 'library',
            'install_path'   => '/usr/share/abraflexi-clientzone',
            'aliases'        => [],
            'dev'            => false,
        ],
        'versions' => $versions,
    ]);
})();
