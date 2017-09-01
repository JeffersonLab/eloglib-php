<?php declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

use Jlab\Eloglib\Logentry;


class LogentryTest extends TestCase
{

    function test_it_sets_config(){
        // The following code/assertions depend on the default .env file in the source directory
        // after loading, its contents should be accessible via getenv()
        $entry = new Logentry();
        $this->assertEquals('http://logbooks.jlab.org/schema/Logentry.xsd', getenv('LOG_ENTRY_SCHEMA_URL'));
        $this->assertEquals('https://logbooks.jlab.org/incoming', getenv('SUBMIT_URL'));
    }

    function test_it_throws_on_bad_config_file(){
        // The following code/assertions depend on the default .env file in the source directory
        // after loading, its contents should be accessible via getenv()
        $entry = new Logentry();
        $this->expectException('Jlab\Eloglib\LogRuntimeException');
        $entry->setConfig('/','noSuchFile.env');
    }

    function test_it_overrides_config(){
        // The default initialization should not override existing env variables
        putenv('SUBMIT_URL=foobar');
        $entry = new Logentry();
        $this->assertEquals('foobar', getenv('SUBMIT_URL'));
        // The third param below forces config file to override existing env
        $entry->setConfig(__DIR__.'/../src','.env',true);
        $this->assertEquals('https://logbooks.jlab.org/incoming', getenv('SUBMIT_URL'));
    }
}




