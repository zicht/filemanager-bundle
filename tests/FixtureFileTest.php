<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace ZichtTest\Bundle\FileManagerBundle;

class FixtureFileTest extends \PHPUnit_Framework_TestCase
{
    function setUp()
    {
        @file_put_contents('/tmp/foo', 'bar');
        @mkdir('/tmp/target');
    }

    function tearDown()
    {
        @unlink('/tmp/foo');
        @unlink('/tmp/target/bar');
        @rmdir('/tmp/target');
    }

    function testOriginalRemainsOnMove()
    {
        $fixture = new \Zicht\Bundle\FileManagerBundle\FixtureFile('/tmp/foo', true);
        $fixture->move('/tmp/target', 'bar');
        clearstatcache();
        $this->assertTrue(is_file('/tmp/foo'));
        $this->assertTrue(is_file('/tmp/target/bar'));
    }

    /**
     * @expectedException \Symfony\Component\HttpFoundation\File\Exception\FileException
     */
    function testErrorOnDirCreation()
    {
        $fixture = new \Zicht\Bundle\FileManagerBundle\FixtureFile('/tmp/foo', true);
        $fixture->move('/doesnotexist', 'bar');
    }

    /**
     * @expectedException \Symfony\Component\HttpFoundation\File\Exception\FileException
     */
    function testErrorOnUnwritableDir()
    {
        chmod('/tmp/target', 0);
        $fixture = new \Zicht\Bundle\FileManagerBundle\FixtureFile('/tmp/foo', true);
        $fixture->move('/tmp/target', 'bar');
        chmod('/tmp/target', 777);
    }


    /**
     * @expectedException \Symfony\Component\HttpFoundation\File\Exception\FileException
     */
    function testCopyFail()
    {
        touch('/tmp/target/bar');
        chmod('/tmp/target/bar', 0);
        $fixture = new \Zicht\Bundle\FileManagerBundle\FixtureFile('/tmp/foo', true);
        $fixture->move('/tmp/target', 'bar');
        chmod('/tmp/target/bar', 777);
    }
}
