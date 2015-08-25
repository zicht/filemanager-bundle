<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace ZichtTest\Bundle\FileManagerBundle\Annotation;

use Zicht\Bundle\FileManagerBundle\Annotation\File;

class FileTest extends \PHPUnit_Framework_TestCase
{
    function testConstructor()
    {
        $t = new File(array('metadata' => true));
        $this->assertEquals($t->settings, array('metadata' => true));
    }
}