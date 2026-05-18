<?php

declare(strict_types=1);

/**
 * CNB Cache - Phinx database adapter.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2021-2024 Vitex Software
 */

require_once '/usr/lib/cnb-cache/autoload.php';

if (file_exists('/etc/cnb-cache/cnb-cache.env')) {
    \Ease\Shared::instanced()->loadConfig('/etc/cnb-cache/cnb-cache.env', true);
}

$prefix = "/usr/lib/cnb-cache/db/";

$sqlOptions = [];

if (strstr(\Ease\Shared::cfg('DB_CONNECTION'), 'sqlite')) {
    $sqlOptions["database"] = "/var/lib/dbconfig-common/sqlite3/cnb-cache/" . basename(\Ease\Shared::cfg("DB_DATABASE"));
}

$engine = new \Ease\SQL\Engine(null, $sqlOptions);
$cfg = [
    'paths' => [
        'migrations' => [$prefix . 'migrations'],
        'seeds' => [$prefix . 'seeds']
    ],
    'environments' =>
    [
        'default_environment' => 'production',
        'production' => [
            'adapter' => \Ease\Shared::cfg('DB_CONNECTION'),
            'name' => $engine->database,
            'connection' => $engine->getPdo($sqlOptions)
        ],
    ]
];

return $cfg;
