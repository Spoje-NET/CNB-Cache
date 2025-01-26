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

Shared::init(
    ['CURRENCIES', 'DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'],
    '../.env',
);

$engine = new \SpojeNet\Cnb\ExchangeRate();

$currency = $_GET['currency'] ?? null;
$when = $_GET['when'] ?? null;

switch ($when) {
    case 'yesterday':
        $age = 1;

        break;
    case 'beforeyesterday':
        $age = 2;

        break;

    default:
        $age = isset($_GET['age']) ? (int) $_GET['age'] : 0;

        break;
}

if ($currency === null) {
    http_response_code(404);
    $currencyList = $engine->getCurrencyList();

    echo '<html><head><title>CNB Cache</title></head><body>';
    echo '<a href="https://github.com/Spoje-NET/CNB-Cache"><img src="cnb-cache.svg" style="width: 100px;" align="right"></a>';
    echo '<ul>';

    foreach ($currencyList as $currency) {
        echo '<li><strong><a href="?currency='.$currency.'">'.$currency.'</a></strong>&nbsp;';
        echo '<a href="?currency='.$currency.'&when=yesterday">yesterday</a>&nbsp;';
        echo '<small><a href="?currency='.$currency.'&when=beforeyesterday">beforeyesterday</a></small>';
        echo '</li>';
    }

    echo '</ul>';
    echo '</body></html>';

    exit;
}

$rateInfo = $engine->getRateInfo($currency, $age);

header('Content-Type: application/json');
echo json_encode($rateInfo);
