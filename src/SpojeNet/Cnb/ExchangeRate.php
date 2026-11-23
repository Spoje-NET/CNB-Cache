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
class ExchangeRate extends \Ease\SQL\Engine
{
    public static string $baseUrl = 'https://www.cnb.cz/cs/financni_trhy/devizovy_trh/kurzy_devizoveho_trhu/denni_kurz.txt';
    private int $keepdays = 3;
    private int $httpcode = 0;

    /**
     * @var array<string, string>
     */
    private array $currencies = [];

    public function __construct()
    {
        $this->setMyTable('rates');
        $this->setObjectName();
        $currencies = explode(',', str_replace(' ', '', \Ease\Shared::cfg('CURRENCIES', 'EUR')));
        $this->currencies = array_combine($currencies, $currencies);
        $this->keepdays = (int) \Ease\Shared::cfg('KEEP_DAYS', 3);
        parent::__construct();
    }

    /**
     * @return array<string, string>
     */
    public function getCurrencyList(): array
    {
        return $this->currencies;
    }

    public function getKeepDays(): int
    {
        return $this->keepdays;
    }

    public function exchangeRateRaw(string $datum): string
    {
        $url = self::$baseUrl.'?date='.date('d.m.Y', strtotime($datum));

        $ch = curl_init();
        curl_setopt($ch, \CURLOPT_URL, $url);
        curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $this->httpcode = curl_getinfo($ch, \CURLINFO_HTTP_CODE);

        if ($response === false) {
            $this->addStatusMessage(sprintf(_('Failed to fetch exchange rates: %s'), curl_error($ch)), 'error');
        }

        curl_close($ch);

        return $response === false ? '' : $response;
    }

    /**
     * Convert CNB CSV to Array.
     *
     * @return array<string, array<string, float|int|string>>
     */
    public function cnbCsv2Data(string $ratesRaw): array
    {
        $rates = [];
        $data = strstr($ratesRaw, "\n") ? explode("\n", $ratesRaw) : [];

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
     * Day the CNB list was published, from its first line ("20.11.2026 #223").
     *
     * For a weekend, a holiday or a day whose list is not out yet CNB answers with the last published one.
     */
    public static function publishedDate(string $ratesRaw): ?string
    {
        return preg_match('/^\s*(\d{2})\.(\d{2})\.(\d{4})\s+#\d+/', $ratesRaw, $m) && checkdate((int) $m[2], (int) $m[1], (int) $m[3]) ? $m[3].'-'.$m[2].'-'.$m[1] : null;
    }

    /**
     * May the list published on $published be kept as the rate of $date?
     *
     * Today's list comes on working days after 14:30; until then the previous one is only temporary.
     */
    public static function isFinal(string $date, string $published, string $today): bool
    {
        if ($date > $today || $published > $date) {
            return false;
        }

        return $date < $today || $published === $date || (int) date('N', strtotime($date)) > 5;
    }

    /**
     * Download rates of a day and keep them when they are final.
     *
     * @return array<string, array<string, float|int|string>> rates of configured currencies by code
     */
    public function storeDay(int $dayBack = 0): array
    {
        $datum = $this->dateBeforeDays($dayBack);
        $ratesRaw = $this->exchangeRateRaw($datum);
        $published = self::publishedDate($ratesRaw);
        $rates = [];

        if ($published === null || $published > $datum) {
            $this->addStatusMessage(sprintf(_('No CNB rates for %s'), $datum), 'error');

            return $rates;
        }

        $final = self::isFinal($datum, $published, $this->today());

        foreach ($this->cnbCsv2Data($ratesRaw) as $currencyData) {
            if (!\array_key_exists($currencyData['code'], $this->currencies)) {
                continue;
            }

            $currencyData['date'] = $datum;
            $currencyData['published'] = $published;
            $rates[$currencyData['code']] = $currencyData;

            if (!$final) {
                continue;
            }

            $conditions = ['code' => $currencyData['code'], 'date' => $datum];

            if ($this->getColumnsFromSQL(['id'], $conditions)) {
                // Rows from older versions may hold the rate of the previous day.
                $this->updateToSQL($currencyData, $conditions);
            } else {
                $this->insertToSQL($currencyData);
                $this->addStatusMessage(sprintf(_('Stored: %s for %s'), $currencyData['code'], $currencyData['date']), 'success');
            }
        }

        return $rates;
    }

    public function dropOlder(int $days): void
    {
        if ($this->deleteFromSQL(['date' => ['<' => $this->dateBeforeDays($days)]]) > 0) {
            $this->addStatusMessage(sprintf(_('Dropped rates older than %d days'), $days), 'success');
        }
    }

    public function shiftDays(): void
    {
        $this->dropOlder($this->getKeepDays());
    }

    /**
     * Exchange rate of a currency. "date" is the day asked for, "published" the day of the CNB list it comes from.
     *
     * @return array<string, float|int|string> empty when the rate is not known
     */
    public function getRateInfo(string $currency, int $age): array
    {
        if ($age < 0 || !\array_key_exists($currency, $this->currencies)) {
            return [];
        }

        $rateInfo = $this->getColumnsFromSQL(['*'], ['code' => $currency, 'date' => $this->dateBeforeDays($age)]);
        $result = $rateInfo ? current($rateInfo) : [];

        // Rows without the publication day come from versions that could store a premature rate.
        if (empty($result['published'])) {
            $result = $this->storeDay($age)[$currency] ?? [];
        }

        if ($result) {
            $result['age'] = $age;
        }

        return $result;
    }

    /**
     * Days from $date to today, null for a malformed or future date.
     */
    public function ageOf(string $date): ?int
    {
        $day = \DateTimeImmutable::createFromFormat('!Y-m-d', $date, new \DateTimeZone('UTC'));

        if ($day === false || $day->format('Y-m-d') !== $date || $date > $this->today()) {
            return null;
        }

        return (int) $day->diff(new \DateTimeImmutable($this->today(), new \DateTimeZone('UTC')))->days;
    }

    /**
     * Today in Prague, where CNB publishes.
     */
    public function today(): string
    {
        return (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Prague')))->format('Y-m-d');
    }

    public function dateBeforeDays(int $daysBack): string
    {
        return (new \DateTimeImmutable($this->today()))->modify('-'.$daysBack.' day')->format('Y-m-d');
    }

    public function getHttpCode(): int
    {
        return $this->httpcode;
    }
}
