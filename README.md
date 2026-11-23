# CNB cache

![cnb-cache](cnb-cache.svg?raw=true)
![Packaging: deb](https://img.shields.io/badge/packaging-.deb-red?logo=debian&logoColor=white)

[![PHP Version](https://img.shields.io/badge/php-%3E%3D%207.4-8892BF.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](https://opensource.org/licenses/MIT)
[![Latest Release](https://img.shields.io/github/v/release/vitexsoftware/cnb-cache.svg)](https://github.com/vitexsoftware/cnb-cache/releases)

Store daily currency rates in SQL database for given time.

Provide simple API like web interface for stored rates obtaining.

## Installation

```shell
sudo apt install lsb-release wget apt-transport-https bzip2


wget -qO- https://repo.vitexsoftware.com/keyring.gpg | sudo tee /etc/apt/trusted.gpg.d/vitexsoftware.gpg
echo "deb [signed-by=/etc/apt/trusted.gpg.d/vitexsoftware.gpg]  https://repo.vitexsoftware.com  $(lsb_release -sc) main" | sudo tee /etc/apt/sources.list.d/vitexsoftware.list
sudo apt update

sudo apt install cnb-cache-DATABASE
```

database can be `mysql` or `sqlite`

Support the apache2 and lighthttpd web servers:

![Web Servers](webservers.png?raw=true)

First Configure the currencies to be cached

![Currency Chooser](currency-chooser.png?raw=true)

Then set the days to keep the cache

![Days to Keep](daystokeep.png?raw=true)

And finally the cache is initialized:

![Initialization](init.png?raw=true)

Data stored in database:

![Database](db.png?raw=true)

Final configuration is stored in `/etc/cnb-cache/cnb-cache.env` file

### Data Obtaining

After installation the currencies listing is available on the `/cnb-cache/` path.

you can use following URL parameters:

* `currency` - currency code (default: CZK)
* `when` - `today` (default), `yesterday` or `beforeyesterday`
* `date` - date of rate in format YYYY-MM-DD; a malformed or future date returns HTTP 400
* `age` - age of rate in days (default: 0)


* `currencies` - list the cached currencies as JSON (`{"currencies":["EUR","USD"],"keep_days":3}`)

* <http://localhost/cnb-cache/?currencies> - cached currencies listing
* <http://localhost/cnb-cache/?currency=eur> - todays EUR rate
* <http://localhost/cnb-cache/?currency=USD&when=yesterday> - yesterday $ rate

```json
{
  "id": 6,
  "date": "2025-01-24",
  "currency": "dolar",
  "amount": 1,
  "code": "USD",
  "rate": 23.958,
  "age": 2,
  "published": "2025-01-24"
}
```

`published` is the day of the CNB list the rate comes from. CNB publishes on working days after 14:30 and the list is valid
until the next one, so on weekends, holidays and before publication it is older than `date`. A rate which is not final yet is
returned but not cached. Unknown or unconfigured currency returns HTTP 404.

The systemd timer updates the cache at 0:01 and on working days at 14:45 and 15:30 (Prague time).

See also: https://github.com/Spoje-NET/pohoda-raiffeisenbank
