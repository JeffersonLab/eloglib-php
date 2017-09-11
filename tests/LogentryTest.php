<?php

namespace Tests;

use DOMDocument;
use Jlab\Eloglib\LogentryUtil;
use PHPUnit\Framework\TestCase;

use Jlab\Eloglib\Logentry;


class LogentryTest extends TestCase
{

    function test_constructor_requires_arguments()
    {
        $this->expectException('Jlab\Eloglib\LogentryException');
        $entry = new Logentry();
    }

    function test_constructor_with_title_and_logbook()
    {
        $entry = new Logentry('test', 'TLOG');
        $this->assertEquals('test', $entry->title);

        $logbooks = $entry->logbooks;
        $this->assertCount(1, $logbooks);
        $this->assertEquals('TLOG', current($logbooks));
    }

    function test_it_sets_default_author()
    {
        $os_user = posix_getpwuid(posix_getuid());
        $entry = new Logentry('test', 'TLOG');
        $this->assertEquals($os_user['name'], $entry->author->username);
    }

    function test_it_sets_logbooks_from_array()
    {
        $entry = new Logentry('test', 'TLOG');
        $entry->setLogbooks(array('ELOG', 'SLOG'));

        $logbooks = $entry->logbooks;
        $this->assertCount(2, $logbooks);
        $this->assertContains('ELOG', $logbooks);
        $this->assertContains('SLOG', $logbooks);

    }

    function test_it_sets_config()
    {
        // The following code/assertions depend on the default .env file in the source directory
        // after loading, its contents should be accessible via getenv()
        $entry = new Logentry('test', 'TLOG');
        $this->assertEquals('http://logbooks.jlab.org/schema/Logentry.xsd', getenv('LOG_ENTRY_SCHEMA_URL'));
        $this->assertEquals('https://logbooks.jlab.org/incoming', getenv('SUBMIT_URL'));
    }

    function test_it_throws_on_bad_config_file()
    {
        // The following code/assertions depend on the default .env file in the source directory
        // after loading, its contents should be accessible via getenv()
        $entry = new Logentry('test', 'TLOG');
        $this->expectException('Jlab\Eloglib\LogentryException');
        $entry->setConfig('/', 'noSuchFile.env');
    }

    function test_it_overrides_config()
    {
        // The default initialization should not override existing env variables
        putenv('SUBMIT_URL=foobar');
        $entry = new Logentry('test', 'TLOG');
        $this->assertEquals('foobar', getenv('SUBMIT_URL'));
        // The third param below forces config file to override existing env
        $entry->setConfig(__DIR__ . '/../src', '.env', true);
        $this->assertEquals('https://logbooks.jlab.org/incoming', getenv('SUBMIT_URL'));
    }

    function test_it_adds_entrymaker()
    {
        $entry = new Logentry('test', 'TLOG');
        $this->assertEmpty($entry->entrymakers);
        $entry->addEntryMaker('bob');
        $this->assertCount(1, $entry->entrymakers);
        $entry->addEntryMaker('sally');
        $this->assertCount(2, $entry->entrymakers);
        $this->assertTrue(array_key_exists('bob', $entry->entrymakers));
        $this->assertTrue(array_key_exists('sally', $entry->entrymakers));
        $this->assertEquals('bob', $entry->entrymakers['bob']->username);
    }

    function test_it_adds_entrymaker_with_attributes()
    {
        $entry = new Logentry('test', 'TLOG');
        $this->assertEmpty($entry->entrymakers);
        $entry->addEntryMaker('bob', array('firstname' => 'Bob', 'lastname' => 'Smith'));
        $this->assertCount(1, $entry->entrymakers);
        $this->assertEquals('bob', $entry->entrymakers['bob']->username);
        $this->assertEquals('Bob', $entry->entrymakers['bob']->firstname);
        $this->assertEquals('Smith', $entry->entrymakers['bob']->lastname);
    }

    function test_it_sets_author()
    {
        $entry = new Logentry('test', 'TLOG');
        $entry->setAuthor('bob');
        $this->assertEquals('bob', $entry->author->username);
    }

    function test_it_sets_author_with_attributes()
    {
        $entry = new Logentry('test', 'TLOG');
        $entry->setAuthor('bob', array('firstname' => 'Bob', 'lastname' => 'Smith'));
        $this->assertEquals('bob', $entry->author->username);
        $this->assertEquals('Bob', $entry->author->firstname);
        $this->assertEquals('Smith', $entry->author->lastname);
    }

    function test_it_adds_logbook()
    {
        $entry = new Logentry('test', 'TLOG');
        $this->assertCount(1, $entry->logbooks);
        $entry->addLogbook('ELOG');
        $this->assertCount(2, $entry->logbooks);
        $this->assertContains('ELOG', $entry->logbooks);
        $this->assertContains('TLOG', $entry->logbooks);
    }

    function test_it_adds_notify()
    {
        putenv('EMAIL_DOMAIN=@example.com');
        $entry = new Logentry('test', 'TLOG');
        $this->assertEmpty($entry->notifications);
        // test basic add by username
        $entry->addNotify('bob');
        $this->assertCount(1, $entry->notifications);
        $this->assertContains('bob@example.com', $entry->notifications);

        // ensure no duplicates
        $entry->addNotify('bob@example.com');
        $this->assertCount(1, $entry->notifications);


        // test add by fully qualified email
        $entry->addNotify('sally@somewhere.org');
        $this->assertCount(2, $entry->notifications);
        $this->assertContains('sally@somewhere.org', $entry->notifications);

    }

    function test_it_adds_references()
    {
        $entry = new Logentry('test', 'TLOG');
        $this->assertEmpty($entry->references);
        $entry->addReference('atlis', 100);
        $this->assertCount(1, $entry->references);
        $this->assertTrue(array_key_exists('atlis', $entry->references));
        $this->assertTrue(array_key_exists('100', $entry->references['atlis']));

        // ensure no duplicates
        $entry->addReference('atlis', 100);
        $this->assertCount(1, $entry->references);
        $this->assertCount(1, $entry->references['atlis']);
    }

    function test_it_adds_tags()
    {
        $entry = new Logentry('test', 'TLOG');
        $this->assertEmpty($entry->tags);
        $entry->addTag('Readme');
        $this->assertCount(1, $entry->tags);
        $entry->addTag('Autolog');
        $this->assertCount(2, $entry->tags);
        $this->assertContains('Readme', $entry->tags);
        $this->assertContains('Autolog', $entry->tags);
    }

    function test_it_sets_body()
    {
        $entry = new Logentry('test', 'TLOG');
        $this->assertEmpty($entry->body);
        $this->assertEmpty($entry->bodyType);
        $entry->setBody('This is the body');
        $this->assertEquals('This is the body', $entry->body);
        $this->assertEquals('text', $entry->bodyType);

        $entry->setBody('Now this is the body', 'full_html');
        $this->assertEquals('Now this is the body', $entry->body);
        $this->assertEquals('full_html', $entry->bodyType);
    }

    function test_it_sets_lognumber()
    {
        $entry = new Logentry('test', 'TLOG');
        $this->assertEmpty($entry->lognumber);
        $entry->setLognumber(12);
        $this->assertEquals(12, $entry->lognumber);
    }

    function test_gets_xml_minimal()
    {
        $entry = new Logentry('test', 'TLOG');
        $xmlString = $entry->getXML();

        $doc = DOMDocument::loadXML($xmlString);
        $this->assertEquals(1, $doc->getElementsByTagName('logbook')->length);
        $this->assertEquals('TLOG', $doc->getElementsByTagName('logbook')->item(0)->nodeValue);
        $this->assertEquals('test', $doc->getElementsByTagName('title')->item(0)->nodeValue);

    }

    function test_gets_xml_complex()
    {
        $entry = new Logentry('test', 'TLOG');
        $entry->addLogbook('ELOG');
        $entry->addEntryMaker('bob');
        $entry->setBody('This is the body of the entry.');
        $entry->addEntryMaker('sally');
        $entry->addReference('atlis', 100);
        $entry->addReference('atlis', 101);
        $entry->addReference('elog', 100000);
        $entry->addAttachment(__DIR__.'/data/imgFile1.png');

        $xmlString = $entry->getXML();

        //var_dump($xmlString);
        //file_put_contents('/tmp/foobar',$xmlString);
        //die;

        $doc = DOMDocument::loadXML($xmlString);
        $this->assertEquals(1, $doc->getElementsByTagName('title')->length);
        $this->assertEquals(1, $doc->getElementsByTagName('body')->length);
        $this->assertEquals(1, $doc->getElementsByTagName('created')->length);
        $this->assertEquals(1, $doc->getElementsByTagName('Author')->length);
        $this->assertEquals(2, $doc->getElementsByTagName('logbook')->length);
        $this->assertEquals(2, $doc->getElementsByTagName('Entrymaker')->length);
        $this->assertEquals(1, $doc->getElementsByTagName('References')->length);
        $this->assertEquals(3, $doc->getElementsByTagName('reference')->length);
        $this->assertEquals(1, $doc->getElementsByTagName('Attachments')->length);
        $this->assertEquals(1, $doc->getElementsByTagName('Attachment')->length);


    }

    function test_it_throws_if_submits_invalid_xml(){
        $entry = new Logentry('test', 'NOSUCHLOG');
        $this->expectException('Jlab\Eloglib\InvalidXMLException');
        $lognumber = $entry->submit();

    }

    function test_it_throws_if_queues_invalid_xml(){
        $entry = new Logentry('test', 'NOSUCHLOG');
        $this->expectException('Jlab\Eloglib\InvalidXMLException');
        $lognumber = $entry->queue();
    }

    function test_it_submits_minimal_valid_test_entry(){

        $entry = new Logentry('test', 'TLOG');
        $lognumber = $entry->submit();
        $this->assertTrue(is_numeric($lognumber));
        $this->assertGreaterThan(0,$lognumber);

    }

}




