<?php
/**
 * Created by PhpStorm.
 * User: theo
 * Date: 9/6/17
 * Time: 10:51 AM
 */

namespace Jlab\Eloglib;


class URLAttachment extends Attachment
{


    /**
     *
     * @param string $url resource address
     * @param string $caption text description of attachment
     * @param string $type explicitly specify mime-type
     * @internal param string $filename full path to file
     */
    function __construct($url, $caption = '', $type = '')
    {
        $this->encoding = 'url';
        $this->filename = urldecode(basename($url));
        $this->caption = $caption;
        $this->data = $url;
        $this->type = $type;

    }


} //class