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

namespace Test\SpojeNet\Cnb;

use PHPUnit\Framework\TestCase;
use SpojeNet\Cnb\ExchangeRate;

/**
 * CNB replaced by fixed lists, "today" under control.
 */
class FakeRate extends ExchangeRate
{
    public string $now = '2026-11-23';

    /**
     * @var array<string, string>
     */
    public array $lists = [];
    public int $downloads = 0;

    public function today(): string
    {
        return $this->now;
    }

    public function exchangeRateRaw(string $datum): string
    {
        ++$this->downloads;
        $known = array_filter(array_keys($this->lists), static fn (string $d): bool => $d <= $datum);

        if ($known === []) {
            return '';
        }

        $last = max($known);

        return date('d.m.Y', strtotime($last))." #1\nzemě|měna|množství|kód|kurz\nEMU|euro|1|EUR|".$this->lists[$last]."\nMaďarsko|forint|100|HUF|6,350\nUSA|dolar|1|USD|21,000\n";
    }
}

class ExchangeRateTest extends TestCase
{
    private FakeRate $rate;
    private string $file;

    protected function setUp(): void
    {
        $this->file = (string) tempnam(sys_get_temp_dir(), 'cnb');

        foreach (['DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => $this->file, 'CURRENCIES' => 'EUR, HUF', 'EASE_LOGGER' => 'memory'] as $key => $value) {
            $_ENV[$key] = $value;
            putenv($key.'='.$value);
        }

        (new \PDO('sqlite:'.$this->file))->exec('CREATE TABLE rates (id INTEGER PRIMARY KEY AUTOINCREMENT, date TEXT, currency TEXT, amount REAL, code TEXT, rate NUMERIC, published TEXT)');
        $this->rate = new FakeRate();
        $this->rate->lists = ['2026-11-19' => '24,425', '2026-11-20' => '24,470'];
    }

    protected function tearDown(): void
    {
        foreach (glob($this->file.'*') ?: [] as $file) {
            unlink($file);
        }
    }

    public function testPublishedDate(): void
    {
        self::assertSame('2026-11-20', ExchangeRate::publishedDate("20.11.2026 #223\nx"));
        self::assertNull(ExchangeRate::publishedDate('<html>'));
        self::assertNull(ExchangeRate::publishedDate('45.11.2026 #223'));
    }

    public function testRateBeforePublicationIsNotKept(): void
    {
        $morning = $this->rate->getRateInfo('EUR', 0);
        self::assertSame([24.47, '2026-11-23', '2026-11-20'], [$morning['rate'], $morning['date'], $morning['published']]);
        self::assertSame(0, $this->stored());
        $this->rate->lists['2026-11-23'] = '24,455';
        $evening = $this->rate->getRateInfo('EUR', 0);
        self::assertSame([24.455, '2026-11-23'], [$evening['rate'], $evening['published']]);
        $this->rate->downloads = 0;
        self::assertSame(24.455, (float) $this->rate->getRateInfo('EUR', 0)['rate']);
        self::assertSame(0, $this->rate->downloads, 'final rate is cached');
    }

    public function testWeekendUsesFriday(): void
    {
        $sunday = $this->rate->getRateInfo('EUR', 1);
        self::assertSame(['2026-11-22', '2026-11-20', 24.47], [$sunday['date'], $sunday['published'], $sunday['rate']]);
        self::assertSame(2, $this->stored());
        $this->rate->now = '2026-11-21';
        $this->rate->getRateInfo('EUR', 0);
        self::assertSame(4, $this->stored(), 'Saturday is final at once');
    }

    public function testPrematureRowsOfOldVersionsHeal(): void
    {
        (new \PDO('sqlite:'.$this->file))->exec("INSERT INTO rates (date, currency, amount, code, rate) VALUES ('2026-11-20', 'euro', 1, 'EUR', 24.425)");
        self::assertSame(24.47, $this->rate->getRateInfo('EUR', 3)['rate']);
        self::assertSame(2, $this->stored(), 'EUR updated, HUF added');
        self::assertSame(24.47, (float) $this->rate->getRateInfo('EUR', 3)['rate']);
    }

    public function testUnknownIsEmpty(): void
    {
        self::assertSame([], $this->rate->getRateInfo('USD', 0), 'not configured');
        self::assertSame([], $this->rate->getRateInfo('EUR', -1));
        self::assertSame([], $this->rate->getRateInfo('EUR', 3000), 'CNB answered nothing');
        self::assertSame(100, $this->rate->getRateInfo('HUF', 3)['amount']);
    }

    public function testAgeOf(): void
    {
        self::assertSame(0, $this->rate->ageOf('2026-11-23'));
        self::assertSame(3, $this->rate->ageOf('2026-11-20'));
        // Summer time must not cost a day.
        self::assertSame(237, $this->rate->ageOf('2026-03-31'));
        self::assertNull($this->rate->ageOf('2026-11-24'));
        self::assertNull($this->rate->ageOf('2026-02-30'));
        self::assertNull($this->rate->ageOf('nonsense'));
    }

    private function stored(): int
    {
        return (int) (new \PDO('sqlite:'.$this->file))->query('SELECT COUNT(*) FROM rates')->fetchColumn();
    }
}
