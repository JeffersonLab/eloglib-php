<?php
/**
 * Created by PhpStorm.
 * User: theo
 * Date: 9/6/17
 * Time: 10:51 AM
 */

namespace Jlab\Eloglib;


class FileAttachment extends Attachment
{


    /**
     *
     * @param string $filename full path to file
     * @param string $caption text description of attachment
     * @param string $type explicitly specify mime-type
     *
     */
    function __construct($filename, $caption = '', $type = '')
    {
        $this->encoding = 'base64';
        $this->readFromFile($filename);
        $this->setMimeType($filename, $type);
        $this->caption = $caption;
    }

    /**
     * Reads contents of a file to $this->data and stores
     * the base filename in $this->filename.
     *
     * @param $filename
     * @throws IOException
     */
    protected function readFromFile($filename){
        $data = file_get_contents($filename);
        if ($data === false) {
            throw new IOException("Unable to read contents of attachment file");
        }
        $this->data = base64_encode($data);
        $this->filename =  basename($filename);
    }

    /**
     * Sets the mime type.
     * @param string $filename full path to file
     * @param mixed $type string type-name or null for auto-detect
     */
    protected function setMimeType($filename, $type)
    {
        if ($type) {
            $this->type = $type;
        } else {
            // Try to get the mime type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $this->type = finfo_file($finfo, $filename);
        }
    }

} //class