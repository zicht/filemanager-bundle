<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace ZichtTest\Bundle\FileManagerBundle\Twig;

class FileManagerExtensionTest extends \PHPUnit_Framework_TestCase
{
    function testExtension()
    {
        $fm = $this->getMockBuilder('Zicht\Bundle\FileManagerBundle\FileManager\FileManager')->disableOriginalConstructor()->getMock();
        $ext = new \Zicht\Bundle\FileManagerBundle\Twig\FileManagerExtension($fm);

        $this->assertEquals('zicht_filemanager', $ext->getName());
        $this->assertArrayHasKey('file_url', $ext->getFunctions());

        $fm->expects($this->once())->method('getFileUrl')->with(1, 2);
        $ext->getFileUrl(1, 2);
    }
}