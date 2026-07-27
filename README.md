# A native PHP interface for making logbook entries

## Requirements

The package requires minimal pre-requisites:

* PHP 5.3 or newer


## Installation

Add the following to your composer.json file:

```
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/JeffersonLab/eloglib-php.git"
    }
],
"require" : {
    "jlab/eloglib-php" : "dev-master"
}

```
Or later once the package is made public, you can get the
 latest version, simply by requiring the project using [Composer](https://getcomposer.org):

```bash
$ composer require jlab/eloglib
```

## Basic Usage

### Minimal
Instantiate an entry with just a title and logbook name and then echo out the XML representation:

```php
#!/usr/bin/php
<?php
require(__DIR__.'/vendor/autoload.php');

$entry = new Jlab\Eloglib\Logentry('Test','TLOG');

print $entry->getXML();
?>

```


## Configuration

Settings are resolved in this order, first match winning:

1. Overrides on the entry itself, via `withConfig()`
2. Values given to `LogentryUtil::setDefaultConfig()`
3. The process environment (`getenv()`)
4. `$_ENV` and `$_SERVER`, which is where a `.env` file lands
5. The `.env` shipped with this package

### From the environment

Nothing to do — the defaults in the bundled `src/.env` apply, and any
environment variable of the same name overrides them.

### Programmatically

Useful when the host application already holds these settings and should not
have to route them through the environment. A framework can do this once at
boot:

```php
use Jlab\Eloglib\LogentryUtil;

LogentryUtil::setDefaultConfig(array(
    'SUBMIT_URL'    => 'https://logbooks.jlab.org/incoming',
    'ELOGCERT_FILE' => '/etc/elog/elogcert',
));
```

In Laravel, for example, from a service provider — which keeps the settings in
`config/`, where they survive `php artisan config:cache`:

```php
LogentryUtil::setDefaultConfig([
    'SUBMIT_URL'    => config('elog.submit_url'),
    'ELOGCERT_FILE' => config('elog.cert_path'),
]);
```

### Per entry

To send a single entry somewhere else without disturbing global state:

```php
$entry = new Jlab\Eloglib\Logentry('Test', 'TLOG');
$entry->withConfig(array('ELOGCERT_FILE' => '/path/to/another.pem'));
```

## Requirements

`ext-curl`, `ext-dom` and `ext-libxml` are needed. `ext-posix` is used when
present but is not required.

## API Reference
Is available:  [API Reference](https://jeffersonlab.github.io/eloglib-php/) 
