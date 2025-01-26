<?php

declare(strict_types=1);

/**
 * This file is part of the CNBCache package
 *
 * https://github.com/Spoje-NET/CNB-cache
 *
 * (c) Spoje.Net IT s.r.o. <https://spojenet.cz>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Ease\Shared;

require_once '../vendor/autoload.php';

\define('APP_NAME', 'CNB Cache Init');

$options = getopt('e::', ['environment::']);

Shared::init(
    ['CURRENCIES', 'DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'],
    \array_key_exists('environment', $options) ? $options['environment'] : (\array_key_exists('e', $options) ? $options['e'] : '../.env'),
);

$engine = new \SpojeNet\Cnb\ExchangeRate();

if (\Ease\Shared::cfg('APP_DEBUG')) {
    $engine->logBanner();
}

$keepDdays = $engine->getKeepDays();

for ($i = 0; $i < $keepDdays; ++$i) {
    $engine->storeDay($i);
}
