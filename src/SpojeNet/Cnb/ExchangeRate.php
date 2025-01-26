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

namespace SpojeNet\Cnb;

/**
 * Description of ExchangeRate.
 *
 * @author Vitex <info@vitexsoftware.cz>
 */
class ExchangeRate extends \Ease\SQL\Engine {

    public static string $baseUrl = 'https://www.cnb.cz/cs/financni_trhy/devizovy_trh/kurzy_devizoveho_trhu/denni_kurz.txt';
    private int $keepdays = 3;
    private int $httpcode = 0;

    /**
     * @var array<string, string>
     */
    private array $currencies = [];

    public function __construct() {
        $this->setMyTable('rates');
        $this->setObjectName();
        $currencies = explode(',', str_replace(' ', '', \Ease\Shared::cfg('CURRENCIES', 'EUR')));
        $this->currencies = array_combine($currencies, $currencies);
        $this->keepdays = intval(\Ease\Shared::cfg('KEEP_DAYS', 3));
        parent::__construct();
    }

    public function getCurrencyList(): array {
        return $this->currencies;
    }

    public function getKeepDays(): int {
        return $this->keepdays;
    }

    public function exchangeRateRaw(string $datum): string {
        $url = self::$baseUrl . '?date=' . date('d.m.Y', strtotime($datum));

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
     * @return array<string, array<string, string>>
     */
    public function cnbCsv2Data(string $ratesRaw) {
        $data = explode("\n", $ratesRaw);

        unset($data[0], $data[1]);

        foreach ($data as $line) {
            $columns = explode('|', $line);

            if (\array_key_exists(3, $columns)) {
                $rates[$columns[3]] = [
                    'currency' => $columns[1],
                    'amount' => (int) $columns[2],
                    'code' => $columns[3],
                    'rate' => (float) str_replace(',', '.', $columns[4]),
                ];
            }
        }

        return $rates;
    }

    /**
     * Insert Data to SQL.
     *
     * @return bool
     */
    public function storeDay(int $dayBack = 0): void {
        $datum = self::dateBeforeDays($dayBack);

        foreach ($this->cnbCsv2Data($this->exchangeRateRaw($datum)) as $currencyData) {
            $currencyData['date'] = $datum;

            if (\array_key_exists($currencyData['code'], $this->currencies)) {
                if (!$this->recordExist(['code' => $currencyData['code'], 'date' => $datum])) {
                    if ($this->insertToSQL($currencyData)) {
                        $this->addStatusMessage(sprintf(_('Stored: %s for %s'), $currencyData['code'], $currencyData['date']), 'success');
                    }
                } else {
                    $this->addStatusMessage(sprintf(_('Already present: %s for %s'), $currencyData['code'], $currencyData['date']), 'warning');
                }
            }
        }
    }

    public function dropOlder(int $days): void {
        if($this->dropFromSQL(['date' => ['<' => self::dateBeforeDays($days)]]) > 0){
            $this->addStatusMessage(sprintf(_('Dropped rates older than %d days'), $days), 'success');
        }
    }

    public function shiftDays(): void {
        $this->dropOlder($this->getKeepDays());
    }

    public function getRateInfo($currency, $age): array {
        
        $rateInfo = $this->getColumnsFromSQL(['*'], ['code' => $currency, 'date' => self::dateBeforeDays($age)]);
        
        if($rateInfo){
            $result = $rateInfo;
        } else {
            $result['message'] = 'no record for '. self::dateBeforeDays($age);
        }
        
        $result['age'] = $age;
        return $result ;
    }

    public static function dateBeforeDays(int $daysBack): string {
        return date('Y-m-d', strtotime('-' . (string) $daysBack . ' day'));
    }
}
