#!/usr/bin/php
<?php

declare(strict_types=1);

/**
 * Invoke Phinx migrations using system PHP class files from php-cakephp-phinx,
 * bypassing the phar at /usr/bin/phinx which bundles its own Composer\InstalledVersions
 * and conflicts with the system /usr/share/php/Composer/InstalledVersions.php.
 */

require_once '/usr/share/php/Symfony/Component/Console/autoload.php';
require_once '/usr/share/php/Symfony/Component/Config/autoload.php';

spl_autoload_register(function (string $class): void {
    if (!str_starts_with($class, 'Phinx\\')) {
        return;
    }
    $file = '/usr/share/php/Phinx/' . str_replace('\\', '/', substr($class, 6)) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

use Phinx\Console\PhinxApplication;

$app = new PhinxApplication();
exit($app->run());
