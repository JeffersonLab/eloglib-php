<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

use Jlab\Eloglib\Logentry;


class LogentryTest extends TestCase
{

    function test_constructor_requires_arguments(){
        $this->expectException('Jlab\Eloglib\LogentryException');
        $entry = new Logentry();
    }

    function test_constructor_with_title_and_logbook(){
        $entry = new Logentry('test','TLOG');
        $this->assertEquals('test', $entry->title);

        $logbooks = $entry->logbooks;
        $this->assertCount(1, $logbooks);
        $this->assertEquals('TLOG',current($logbooks));
    }


    function test_it_sets_logbooks_from_array(){
        $entry = new Logentry('test','TLOG');
        $entry->setLogbooks(array('ELOG','SLOG'));

        $logbooks = $entry->logbooks;
        $this->assertCount(2, $logbooks);
        $this->assertContains('ELOG',$logbooks);
        $this->assertContains('SLOG',$logbooks);

    }

    function test_it_sets_config(){
        // The following code/assertions depend on the default .env file in the source directory
        // after loading, its contents should be accessible via getenv()
        $entry = new Logentry('test','TLOG');
        $this->assertEquals('http://logbooks.jlab.org/schema/Logentry.xsd', getenv('LOG_ENTRY_SCHEMA_URL'));
        $this->assertEquals('https://logbooks.jlab.org/incoming', getenv('SUBMIT_URL'));
    }

    function test_it_throws_on_bad_config_file(){
        // The following code/assertions depend on the default .env file in the source directory
        // after loading, its contents should be accessible via getenv()
        $entry = new Logentry('test','TLOG');
        $this->expectException('Jlab\Eloglib\LogRuntimeException');
        $entry->setConfig('/','noSuchFile.env');
    }

    function test_it_overrides_config(){
        // The default initialization should not override existing env variables
        putenv('SUBMIT_URL=foobar');
        $entry = new Logentry('test','TLOG');
        $this->assertEquals('foobar', getenv('SUBMIT_URL'));
        // The third param below forces config file to override existing env
        $entry->setConfig(__DIR__.'/../src','.env',true);
        $this->assertEquals('https://logbooks.jlab.org/incoming', getenv('SUBMIT_URL'));
    }
}




