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

namespace SpojeNet\Cnb;

/**
 * Description of ExchangeRate.
 *
 * @author Vitex <info@vitexsoftware.cz>
 */
class ExchangeRate extends \Ease\SQL\Engine {

    private int $httpcode = 0;
    /**
     * @var array<string,string>
     */
    private array $currencies = [];

    public static string $baseUrl = 'https://www.cnb.cz/cs/financni_trhy/devizovy_trh/kurzy_devizoveho_trhu/denni_kurz.txt';

    public function __construct() {
        $this->setMyTable('rates');
        $this->setObjectName();
        $currencies = explode(',', \Ease\Shared::cfg('CURRENCIES', 'EUR'));
        $this->currencies = array_combine($currencies, $currencies);
        parent::__construct();
    }

    public function exchangeRateRaw(string $datum): string {
        $url = self::$baseUrl.'?date=' . date('d.m.Y', strtotime($datum));

        $ch = curl_init();
        curl_setopt($ch, \CURLOPT_URL, $url);
        curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $this->httpcode = curl_getinfo($ch, \CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $response;
    }

    /**
     * Convert CNB CSV to Array.
     *
     * @param string $ratesRaw
     *
     * @return array
     */
    public function cnbCsv2Data(string $ratesRaw) {
        $data = explode("\n", $ratesRaw);

        unset($data[0]);
        unset($data[1]);

        foreach ($data as $line) {
            $columns = explode('|', $line);

            if (array_key_exists(3, $columns)) {

                $rates[$columns[3]] = [
                    'currency' => $columns[1],
                    'amount' => intval($columns[2]),
                    'code' => $columns[3],
                    'rate' => floatval(str_replace(',', '.', $columns[4])),
                ];
            }
        }

        return $rates;
    }

    /**
     * Insert Data to SQL.
     *
     * @param array $data
     *
     * @return bool
     */
    public function storeDay(int $dayBack = 0): void {
        $datum = self::dateBeforeDays($dayBack);

        foreach ($this->cnbCsv2Data($this->exchangeRateRaw($datum)) as $currencyData) {
            $currencyData['date'] = $datum;
            if (array_key_exists($currencyData['code'], $this->currencies)) {
                if (!$this->recordExist(['code' => $currencyData['code'], 'date' => $datum])) {
                    if ($this->insertToSQL($currencyData)) {
                        $this->addStatusMessage(sprintf(_('Stored: %s for %s'), $currencyData['code'], $currencyData['date']),'success');
                    }
                }
            }
        }
    }

    public function renderDay(): void {

    }

    public function shiftDays(): void {

    }

    public function getRateInfo($currency, $age): array {
        $rate = $this->getColumnsFromSQL(['*'], ['code' => $currency, 'date'=> self::dateBeforeDays($age) ]);
        return $rate;
    }
    
    public static function dateBeforeDays(int $daysBack): string {
        return  date('Y-m-d', strtotime('-' . (string) $daysBack . ' day'));
    }

}
