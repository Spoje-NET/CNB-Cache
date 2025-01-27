# CNB cache

![cnb-cache](cnb-cache.svg?raw=true)

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
  "age": 2
}
```

The systemd-crond service is started and the cache is updated every day at 0:01 AM

See also: https://github.com/Spoje-NET/pohoda-raiffeisenbank
