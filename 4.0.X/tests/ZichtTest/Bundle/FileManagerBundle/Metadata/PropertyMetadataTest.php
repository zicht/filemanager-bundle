<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace ZichtTest\Bundle\FileManagerBundle\Metadata;

class PropertyMetadataTest extends \PHPUnit_Framework_TestCase
{
    public $mockProp;


    function testFilemanagerMetadata()
    {
        $o = new \Zicht\Bundle\FileManagerBundle\Metadata\PropertyMetadata(__CLASS__, 'mockProp');
        $this->assertTrue(property_exists($o, 'fileManager'));
        $this->assertEmpty($o->fileManager);
    }
}