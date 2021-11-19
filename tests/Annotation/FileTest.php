<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace ZichtTest\Bundle\FileManagerBundle\Annotation;

use PHPUnit\Framework\TestCase;
use Zicht\Bundle\FileManagerBundle\Annotation\File;

class FileTest extends TestCase
{
    function testConstructor()
    {
        $t = new File(['metadata' => true]);
        $this->assertEquals($t->settings, ['metadata' => true]);
    }
}
