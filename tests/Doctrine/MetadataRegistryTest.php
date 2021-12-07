<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace ZichtTest\Bundle\FileManagerBundle\Doctrine;

use PHPUnit\Framework\TestCase;
use \Zicht\Bundle\FileManagerBundle\Doctrine\MetadataRegistry;

class MyEntity
{
    public $myField = null;
}

/**
 * @covers \Zicht\Bundle\FileManagerBundle\Doctrine\MetadataRegistry
 */
class MetadataRegistryTest extends TestCase
{
    /**
     * @dataProvider cases
     * @param bool $hasFileManager
     * @param array $expectedArray
     */
    function testGetManagedFields($hasFileManager, $expectedArray)
    {
        $factory = $this->createMock('Metadata\MetadataFactoryInterface');
        $data = new \Metadata\ClassMetadata('ZichtTest\Bundle\FileManagerBundle\Doctrine\MyEntity');
        $prop = new \Zicht\Bundle\FileManagerBundle\Metadata\PropertyMetadata('ZichtTest\Bundle\FileManagerBundle\Doctrine\MyEntity', 'myField');
        $data->addPropertyMetadata($prop);
        if ($hasFileManager) {
            $prop->fileManager = true;
        }

        $o = new MyEntity();
        $factory->expects($this->once())->method('getMetadataForClass')->with(get_class($o))->will($this->returnValue($data));

        $reg = new \Zicht\Bundle\FileManagerBundle\Doctrine\MetadataRegistry($factory);
        $this->assertEquals($expectedArray, $reg->getManagedFields($o));
        $this->assertEquals($hasFileManager, $reg->isManaged($o));
    }
    public function cases()
    {
        return [
            [true, ['myField']],
            [false, []],
        ];
    }
}
