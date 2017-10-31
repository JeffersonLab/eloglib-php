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



## API Reference
Is available:  [API Reference](https://jeffersonlab.github.io/eloglib-php/) 
