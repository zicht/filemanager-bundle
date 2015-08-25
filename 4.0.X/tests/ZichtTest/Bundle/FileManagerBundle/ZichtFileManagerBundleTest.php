<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace ZichtTest\Bundle\FileManagerBundle;

class ZichtFileManagerBundleTest extends \PHPUnit_Framework_TestCase
{
    function testConstruction()
    {
        new \Zicht\Bundle\FileManagerBundle\ZichtFileManagerBundle();
    }
}