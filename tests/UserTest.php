<?php

namespace Tests;

use Jlab\Eloglib\User;
use PHPUnit\Framework\TestCase;

use Jlab\Eloglib\Logentry;


class UserTest extends TestCase
{


    function test_constructor(){
        // Ensure that we can create user objects, including with optional attributes
        $user = new User('foobar',array('firstname' => 'foo', 'lastname'=>'bar'));

        $this->assertEquals('foobar', $user->username);
        $this->assertEquals('foo', $user->firstname);
        $this->assertEquals('bar', $user->lastname);
    }

    function test_magic_methods(){
        // Ensure that we can set and retrieve the protected class properties via the "magic" __get and __set
        // methods.
        $user = new User('foobar');
        $user->firstname = 'foo';
        $user->lastname = 'bar';

        $this->assertEquals('foobar', $user->username);
        $this->assertEquals('foo', $user->firstname);
        $this->assertEquals('bar', $user->lastname);
    }

    function test_constructor_throws_exceptions(){

        $this->expectException('Jlab\Eloglib\UserException');
        $user = new User(str_repeat('z',61));

    }

    function test_setter_throws_exception(){
        // Setters with other attributes too long.
        $user = new User('foobar');

        $this->expectException('Jlab\Eloglib\UserException');
        $user->firstname = str_repeat('z',256);
    }


}




