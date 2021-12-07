<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace ZichtTest\Bundle\FileManagerBundle;

use PHPUnit\Framework\TestCase;

class FixtureFileTest extends TestCase
{
    public function setUp(): void
    {
        @file_put_contents('/tmp/foo', 'bar');
        @mkdir('/tmp/target');
    }

    public function tearDown(): void
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

    function testErrorOnDirCreation()
    {
        $this->expectException('\Symfony\Component\HttpFoundation\File\Exception\FileException');
        $fixture = new \Zicht\Bundle\FileManagerBundle\FixtureFile('/tmp/foo', true);
        $fixture->move('/doesnotexist', 'bar');
    }

    function testErrorOnUnwritableDir()
    {
        $this->expectException('\Symfony\Component\HttpFoundation\File\Exception\FileException');
        chmod('/tmp/target', 0);
        $fixture = new \Zicht\Bundle\FileManagerBundle\FixtureFile('/tmp/foo', true);
        $fixture->move('/tmp/target', 'bar');
        chmod('/tmp/target', 777);
    }

    function testCopyFail()
    {
        $this->expectException('\Symfony\Component\HttpFoundation\File\Exception\FileException');
        touch('/tmp/target/bar');
        chmod('/tmp/target/bar', 0);
        $fixture = new \Zicht\Bundle\FileManagerBundle\FixtureFile('/tmp/foo', true);
        $fixture->move('/tmp/target', 'bar');
        chmod('/tmp/target/bar', 777);
    }
}
