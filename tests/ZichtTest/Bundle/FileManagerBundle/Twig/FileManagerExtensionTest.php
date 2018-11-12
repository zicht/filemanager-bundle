<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace ZichtTest\Bundle\FileManagerBundle\Twig;

class FileManagerExtensionTest extends \PHPUnit_Framework_TestCase
{
    function testExtensionName()
    {
        list($ext) = $this->getExtension();

        $this->assertEquals('zicht_filemanager', $ext->getName());
    }

    public function getExtension()
    {
        $fm  = $this->getMockBuilder('Zicht\Bundle\FileManagerBundle\FileManager\FileManager')->disableOriginalConstructor()->getMock();
        $ext = new \Zicht\Bundle\FileManagerBundle\Twig\FileManagerExtension($fm);

        return array($ext, $fm);
    }


    function testFileUrlIsATwigFunction()
    {
        list($ext) = $this->getExtension();
        $this->assertArrayHasKey('file_url', $ext->getFunctions());
    }

    function testGetFileUrlDelegatesToFileManagerGetFileUrl()
    {
        list($ext, $fm) = $this->getExtension();

        $fm->expects($this->once())->method('getFileUrl')->with(1, 2)->will($this->returnValue('foo'));
        $ret = $ext->getFileUrl(1, 2);

        $this->assertEquals('foo', $ret);
    }
}