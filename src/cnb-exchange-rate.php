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

// Nastavení MultiFlexi

$options = getopt('o::e::', ['output::environment::']);

Shared::init(
    ['CURRENCY'],
    \array_key_exists('environment', $options) ? $options['environment'] : (\array_key_exists('e', $options) ? $options['e'] : '../.env'),
);
$destination = \array_key_exists('output', $options) ? $options['output'] : \Ease\Shared::cfg('RESULT_FILE', 'php://stdout');

$datum = Shared::cfg('DATE', date('Y-m-d'));

if ($datum === 'yesterday') {
    $datum = date('Y-m-d', strtotime('-1 day'));
}

$exitcode = 0;

$url = 'https://www.cnb.cz/cs/financni_trhy/devizovy_trh/kurzy_devizoveho_trhu/denni_kurz.txt?date='.date('d.m.Y', strtotime($datum));

$ch = curl_init();
curl_setopt($ch, \CURLOPT_URL, $url);
curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, \CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpcode !== 200) {
    $exitcode = $httpcode;

    exit($exitcode);
}

$data = explode("\n", $response);

$engine = new \Ease\Sand();

array_shift($data);
array_shift($data);

$currency = Shared::cfg('CURRENCY');
$kurz = [];
$found = false;

foreach ($data as $line) {
    $columns = explode('|', $line);

    if (isset($columns[3]) && $columns[3] === $currency) {
        $kurz = [
            'date' => date('d.m.Y', strtotime($datum)),
            'country' => $columns[0],
            'currency' => $columns[1],
            'amount' => $columns[2],
            'code' => $columns[3],
            'rate' => $columns[4],
        ];
        $found = true;

        break;
    }
}

if (!$found) {
    $exitcode = 1;
} else {
    $written = file_put_contents($destination, json_encode($kurz, Shared::cfg('DEBUG') ? \JSON_PRETTY_PRINT : 0));
    $engine->addStatusMessage(sprintf(_('Saving result to %s'), $destination), $written ? 'success' : 'error');
}

exit($exitcode);
