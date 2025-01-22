<?php

declare(strict_types=1);

/**
 * This file is part of the CNBExchangeRate package
 *
 * https://github.com/Spoje-NET/CNB-Tools
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

$currency = isset($_GET['currency']) ? $_GET['currency'] : null;
$age = isset($_GET['age']) ? (int)$_GET['age'] : 0;

if ($currency === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Currency parameter is required']);
    exit;
}

$rateInfo = $engine->getRateInfo($currency, $age);

header('Content-Type: application/json');
echo json_encode($rateInfo);

