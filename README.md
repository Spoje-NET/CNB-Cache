# CNB cache

![cnb-cache](cnb-cache.svg?raw=true)


http://localhost/CNB-cache/src/rate.php?currency=eur - todays EUR rate
http://localhost/CNB-cache/src/rate.php?currency=usd&age=1 - yesterday $ rate


## Struktura projektu

- **src/app.php**: Hlavní logika aplikace, která načítá potřebné knihovny, nastavuje připojení k databázi MultiFlexi, získává kurzovní lístek z API ČNB a ukládá data do JSON souboru.
- **vendor/**: Adresář obsahující závislosti projektu spravované pomocí Composeru.
- **composer.json**: Konfigurační soubor pro Composer, obsahující informace o závislostech a dalších nastaveních.

## Instalace

1. Nainstalujte MultiFlexi podle dokumentace na GitHubu: [MultiFlexi GitHub](https://github.com/VitexSoftware/MultiFlexi).
2. Nainstalujte závislosti pomocí Composeru:
   ```
   composer install
   ```

## Spuštění aplikace

Ujistěte se, že máte správně nastavené prostředí a databázi. Poté aplikaci spusťte pomocí příkazu:
```
php src/app.php
```

## Použití

Před spuštěním aplikace upravte proměnné `$datum` a `$mena` v souboru `src/app.php` podle vašich potřeb. Aplikace stáhne kurzovní lístek pro zadané datum a měnu a uloží jej do souboru `kurzovni_listek.json`.