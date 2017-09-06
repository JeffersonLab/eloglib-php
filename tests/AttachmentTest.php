<?php

namespace Tests;

use Jlab\Eloglib\FileAttachment;
use Jlab\Eloglib\URLAttachment;
use Jlab\Eloglib\User;
use PHPUnit\Framework\TestCase;

use Jlab\Eloglib\Logentry;


class AttachmentTest extends TestCase
{


    function test_minimal_image_file_attachment(){
        $filename = __DIR__.'/data/imgFile1.png';
        $data = file_get_contents($filename);
        $attachment = new FileAttachment($filename);
        $this->assertEquals($data, base64_decode($attachment->data));
        $this->assertEquals('image/png', $attachment->type);
        $this->assertEquals('imgFile1.png', $attachment->caption);  // b/c of magic method!
    }

    function test_file_attachment_explicit_caption_and_type(){
        $filename = __DIR__.'/data/imgFile1.png';
        $attachment = new FileAttachment($filename,'my caption', 'octet/stream');
        $this->assertEquals('octet/stream', $attachment->type);
        $this->assertEquals('my caption', $attachment->caption);
    }

    function test_minimal_image_url_attachment(){
        $url = 'http://somewhere.com/imgFile1.png';
        $attachment = new URLAttachment($url);
        $this->assertEquals($url, $attachment->data);
        $this->assertEquals('', $attachment->type);
        $this->assertEquals('imgFile1.png', $attachment->caption);  // b/c of magic method!
    }

    function test_img_attachment_explicit_caption_and_type(){
        $url = 'http://somewhere.com/imgFile1.png';
        $attachment = new URLAttachment($url,'my caption', 'octet/stream');
        $this->assertEquals('octet/stream', $attachment->type);
        $this->assertEquals('my caption', $attachment->caption);
    }

}




