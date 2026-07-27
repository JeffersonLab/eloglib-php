<?php

namespace Tests;

use Jlab\Eloglib\Logentry;
use Jlab\Eloglib\LogentryUtil;
use PHPUnit\Framework\TestCase;

/**
 * Configuration resolution.
 *
 * Deliberately free of setUp()/tearDown() and of assertions whose names have
 * changed between PHPUnit majors, so these run unchanged on the version pinned
 * in composer.json and on a current one.
 */
class ConfigTest extends TestCase
{
    /**
     * The bundled .env is loaded through Dotenv, which writes to $_ENV rather
     * than to the process environment. Reading with getenv() alone therefore
     * misses the package's own defaults, leaving entries submitted to a bare
     * path with no certificate.
     */
    function test_it_resolves_the_settings_shipped_with_the_package()
    {
        LogentryUtil::clearDefaultConfig();
        new Logentry('test', 'TLOG');   // loads the bundled .env

        $this->assertNotNull(LogentryUtil::config('SUBMIT_URL'));
        $this->assertNotNull(LogentryUtil::config('LOG_ENTRY_SCHEMA_URL'));
        $this->assertNotNull(LogentryUtil::config('ELOGCERT_FILE'));
    }

    function test_it_reads_from_the_process_environment()
    {
        LogentryUtil::clearDefaultConfig();
        putenv('ELOGLIB_TEST_ONLY=from-putenv');

        $this->assertEquals('from-putenv', LogentryUtil::config('ELOGLIB_TEST_ONLY'));

        putenv('ELOGLIB_TEST_ONLY');
    }

    function test_it_reads_from_the_env_superglobal()
    {
        LogentryUtil::clearDefaultConfig();
        $_ENV['ELOGLIB_TEST_ONLY'] = 'from-env';

        $this->assertEquals('from-env', LogentryUtil::config('ELOGLIB_TEST_ONLY'));

        unset($_ENV['ELOGLIB_TEST_ONLY']);
    }

    function test_it_reads_from_the_server_superglobal()
    {
        LogentryUtil::clearDefaultConfig();
        $_SERVER['ELOGLIB_TEST_ONLY'] = 'from-server';

        $this->assertEquals('from-server', LogentryUtil::config('ELOGLIB_TEST_ONLY'));

        unset($_SERVER['ELOGLIB_TEST_ONLY']);
    }

    function test_it_returns_the_given_default_when_a_setting_is_absent()
    {
        LogentryUtil::clearDefaultConfig();

        $this->assertEquals('fallback', LogentryUtil::config('ELOGLIB_NO_SUCH_SETTING', 'fallback'));
        $this->assertNull(LogentryUtil::config('ELOGLIB_NO_SUCH_SETTING'));
    }

    function test_explicit_configuration_beats_the_environment()
    {
        LogentryUtil::clearDefaultConfig();
        putenv('ELOGLIB_TEST_ONLY=from-putenv');
        LogentryUtil::setDefaultConfig(array('ELOGLIB_TEST_ONLY' => 'explicit'));

        $this->assertEquals('explicit', LogentryUtil::config('ELOGLIB_TEST_ONLY'));

        putenv('ELOGLIB_TEST_ONLY');
        LogentryUtil::clearDefaultConfig();
    }

    function test_explicit_configuration_can_be_cleared()
    {
        LogentryUtil::setDefaultConfig(array('ELOGLIB_TEST_ONLY' => 'explicit'));
        LogentryUtil::clearDefaultConfig();

        $this->assertNull(LogentryUtil::config('ELOGLIB_TEST_ONLY'));
    }

    function test_an_entry_overrides_the_explicit_configuration()
    {
        LogentryUtil::clearDefaultConfig();
        LogentryUtil::setDefaultConfig(array('ELOGCERT_FILE' => '/etc/elog/default.pem'));

        $entry = new Logentry('test', 'TLOG');
        $entry->withConfig(array('ELOGCERT_FILE' => '/etc/elog/special.pem'));

        $this->assertEquals('/etc/elog/special.pem', LogentryUtil::config('ELOGCERT_FILE', null, $entry));
        $this->assertEquals('/etc/elog/default.pem', LogentryUtil::config('ELOGCERT_FILE'));

        LogentryUtil::clearDefaultConfig();
    }

    function test_with_config_is_chainable()
    {
        $entry = new Logentry('test', 'TLOG');

        $this->assertSame($entry, $entry->withConfig(array('SUBMIT_URL' => 'https://one.example')));
    }

    function test_certificate_file_honours_an_absolute_path_from_configuration()
    {
        LogentryUtil::clearDefaultConfig();
        LogentryUtil::setDefaultConfig(array('ELOGCERT_FILE' => '/etc/elog/elogcert'));

        $this->assertEquals('/etc/elog/elogcert', LogentryUtil::certificateFile());

        LogentryUtil::clearDefaultConfig();
    }

    function test_certificate_file_honours_a_per_entry_override()
    {
        LogentryUtil::clearDefaultConfig();
        LogentryUtil::setDefaultConfig(array('ELOGCERT_FILE' => '/etc/elog/elogcert'));

        $entry = new Logentry('test', 'TLOG');
        $entry->withConfig(array('ELOGCERT_FILE' => '/tmp/other.pem'));

        $this->assertEquals('/tmp/other.pem', LogentryUtil::certificateFile($entry));

        LogentryUtil::clearDefaultConfig();
    }

    /**
     * ext-posix is routinely absent from container images, and was previously
     * needed merely to name the temp file that direct submission writes.
     */
    function test_queue_file_names_do_not_require_ext_posix()
    {
        $name = LogentryUtil::queueFileName();

        $this->assertTrue(substr($name, -4) === '.xml', 'queue file should be XML');
        $this->assertTrue(strpos($name, (string) getmypid()) !== false, 'name should carry the pid');
    }

    function test_the_default_author_is_set_without_ext_posix()
    {
        $entry = new Logentry('test', 'TLOG');

        $this->assertNotEmpty($entry->author->username);
    }
}
