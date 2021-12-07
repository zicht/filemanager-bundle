<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace ZichtTest\Bundle\FileManagerBundle;

use PHPUnit\Framework\TestCase;

class ZichtFileManagerBundleTest extends TestCase
{
    function testConstruction()
    {
        $this->expectNotToPerformAssertions();
        new \Zicht\Bundle\FileManagerBundle\ZichtFileManagerBundle();
    }
}
