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
 * Class Attachment
 *
 * @package Jlab\Eloglib
 */
abstract class Attachment
{
    /**
     * Attachment content.
     *
     * Can be base64-encoded bytes of the file or just a URL
     *
     * @var string
     */
    protected $data;

    /**
     * Attachment MIME-TYPE.
     *
     * @var string
     */
    protected $type;


    /**
     * Indicates the format of $data.
     *
     * Valid values are "base64" or "url"
     *
     * @var string
     *
     */
    protected $encoding;


    /**
     * Text describing the attachment
     * @var string username
     *
     */
    protected $caption;

    /**
     * Return Attachment object as an XML DOMDocument
     *
     * @param string $name A name to use for the DOMElement being returned.
     *
     * @return string
     */
    function getXML($name = 'Attachment')
    {
        $xw = new xmlWriter();
        $xw->openMemory();
        $xw->setIndent(true);
        $xw->startElement($name);
        $xw->writeElement('caption', $this->getCaption());
        $xw->writeElement('type', $this->type);
        $xw->writeElement('filename', $this->filename);
        $this->xmlWriteData($xw);
        $xw->endElement();
        return $xw->outputMemory(true);
    }

    /**
     * Write the data and its encoding attribute to XML.
     * @param \xmlWriter $xw
     */
    protected function xmlWriteData(xmlWriter $xw){
        $xw->startElement('data');
        $xw->writeAttribute('encoding', $this->encoding);
        $xw->text($this->data);
        $xw->endElement();
    }


    /**
     * Returns the caption text if it is set, or the base filename
     * of the attachment if it is not.
     *
     * This method can be called explicitly, but is also an interceptor method
     * that will get invoked when a client reads $this->caption variable.
     *
     * @see __get()
     */
    function getCaption()
    {
        if ($this->caption) {
            return $this->caption;
        } else {
            return basename($this->filename);
        }
    }

    /**
     * Magic method allows controlled access to class properties
     * by checking to see if a "getVariableName()" method exists, and if so,
     * invoking it in lieu of direct read of "variableName".
     *
     * @param string $var
     * @return mixed
     */
    function __get($var)
    {
        if (property_exists($this, $var)) {
            $getterFunction = 'get' . ucfirst($var);
            if (method_exists($this, $getterFunction)) {
                return $this->$getterFunction();
            } else {
                return $this->$var;
            }
        }

        $trace = debug_backtrace();
        trigger_error(
            'Undefined property via __get(): ' . $var .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'], E_USER_NOTICE);
        return null;
    }


}