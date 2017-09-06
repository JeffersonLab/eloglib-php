<?php
/**
 * Created by PhpStorm.
 * User: theo
 * Date: 9/1/17
 * Time: 1:53 PM
 */

namespace Jlab\Eloglib;

use XMLWriter;

/**
 * Class User
 *
 * @package Jlab\Eloglib
 */
class User
{
    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $firstname;


    /**
     * @var string username
     *
     */
    protected $lastname;


    /**
     * User constructor.
     * @param string $username
     * @param array $attributes associative array of additional user attributes
     * @throws UserException
     */
    function __construct($username, array $attributes = array())
    {
        $this->setUsername($username);

        unset($attributes['username']);  //ensure no clobbering of $username
        foreach ($attributes as $key => $val) {
            if (!$this->setProperty($key, $val)) {
                throw new UserException('Invalid attribute passed to User constructor');
            }
        }
    }


    /**
     * @param string $username
     * @throws UserException
     */
    function setUsername($username)
    {
        if (strlen($username) <= 60) {     //Drupal users table limit
            $this->username = $username;
        } else {
            throw new UserException('Username exceeds character limit');
        }
    }

    /**
     * Attempts to set the named object property.
     *
     * Returns true if the property exists to be set, false otherwise.
     *
     * @param string $var
     * @param mixed $val
     * @return bool
     */
    protected function setProperty($var, $val)
    {
        if (property_exists($this, $var)) {
            $setterFunction = 'set' . ucfirst($var);
            if (method_exists($this, $setterFunction)) {
                $this->$setterFunction($val);
            } else {
                $this->$var = $val;
            }
            return true;
        }
        return false;
    }

    /**
     * @param string $firstname
     * @throws UserException
     */
    function setFirstname($firstname)
    {
        if (strlen($firstname) <= 255) {     //Drupal field_data_field_first_name table limit
            $this->firstname = $firstname;
        } else {
            throw new UserException('First name  exceeds character limit');
        }
    }

    /**
     * @param string $lastname
     * @throws UserException
     */
    function setLastname($lastname)
    {
        if (strlen($lastname) <= 255) {     //Drupal field_data_field_last_name table limit
            $this->lastname = $lastname;
        } else {
            throw new UserException('Last name  exceeds character limit');
        }
    }

    /**
     * Magic method allows controlled access to class properties.
     *
     * @param string $var
     * @return mixed
     */
    function __get($var)
    {
        if (property_exists($this, $var)) {
            return $this->$var;
        }

        $trace = debug_backtrace();
        trigger_error(
            'Undefined property via __get(): ' . $var .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'], E_USER_NOTICE);
        return null;
    }

    /**
     * Magic method intercepts setting of class properties.
     *
     * @param string $var
     * @param mixed $val
     * @return bool
     */
    function __set($var, $val)
    {
        if ($this->setProperty($var, $val)) {
            return true;
        }

        $trace = debug_backtrace();
        trigger_error(
            'Undefined property via __set(): ' . $var .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'], E_USER_NOTICE);
        return false;
    }

    /**
     * Return User object as an XML DOMDocument
     *
     * @param string $name A name to use for the DOMElement.
     *
     * @return string
     */
    function getXML($name = 'User')
    {
        $xw = new xmlWriter();
        $xw->openMemory();
        $xw->setIndent(true);
        $xw->startElement($name);
        $xw->writeElement('username', $this->username);

        if ($this->firstname){
            $xw->writeElement('firstname', $this->firstname);
        }

        if ($this->lastname) {
            $xw->writeElement('lastname', $this->lastname);
        }

        $xw->endElement();
        return $xw->outputMemory(true);

    }
}