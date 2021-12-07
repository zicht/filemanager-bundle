<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace ZichtTest\Bundle\FileManagerBundle\Metadata;

use PHPUnit\Framework\TestCase;

class PropertyMetadataTest extends TestCase
{
    public $mockProp;


    function testFilemanagerMetadata()
    {
        $o = new \Zicht\Bundle\FileManagerBundle\Metadata\PropertyMetadata(__CLASS__, 'mockProp');
        $this->assertTrue(property_exists($o, 'fileManager'));
        $this->assertEmpty($o->fileManager);
    }
}
