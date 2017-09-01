<?php
/**
 * Class Logentry
 */

namespace Jlab\Eloglib;

use Dotenv\Dotenv;
/**
 * Class Logentry
 *
 * An electronic log book log entry.
 *
 * @package Jlab\Eloglib
 */
class Logentry
{

    /**
     * @var Dotenv
     */
    protected $config;


    function __construct()
    {
        $this->setConfig(__DIR__, '.env');
    }


    /**
     * Loads configuration from a .env file.
     * Defaults to the .env file included with the package.
     *
     * @param string $dir
     * @param string $file
     * @param bool   $overload whether config file should replace existing settings (def: FALSE)
     * @throws LogRuntimeException
     */
    function setConfig($dir, $file, $overload = false){
        try{
            $this->config = new Dotenv($dir, $file);
            if ($overload) {
                $this->config->overload();
            }else{
                $this->config->load();
            }
            // Throws if any required env variables are missing
            $this->config->required(array('LOG_ENTRY_SCHEMA_URL','SUBMIT_URL','DEFAULT_UNIX_QUEUE_PATH'));
        }catch (\Exception $e){
            throw new LogRuntimeException($e->getMessage());
        }
    }


}