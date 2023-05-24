<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace ZichtTest\Bundle\FileManagerBundle\Metadata\Driver;

use PHPUnit\Framework\TestCase;
use Zicht\Bundle\FileManagerBundle\Annotation\File;

class MyClass
{
    public $file;
    public $noFile;
}


class AnnotationDriverTest extends TestCase
{
    public function setUp(): void
    {
        $this->reader = $this->getMockBuilder('Doctrine\Common\Annotations\Reader')
            ->disableOriginalConstructor()
            ->addMethods(['getPropertyAnnotation'])
            ->getMock();

        $this->driver = new \Zicht\Bundle\FileManagerBundle\Metadata\Driver\AnnotationDriver($this->reader);
    }

    function testLoadMetadataForClass()
    {
        $refl = new \ReflectionClass(__NAMESPACE__ . '\\MyClass');
        $prop1 = $refl->getProperty('file');
        $prop2 = $refl->getProperty('noFile');
        $this->reader->expects($this->exactly(2))->method('getPropertyAnnotation')
            ->withConsecutive([$prop1], [$prop2])->will($this->returnValue(new File([])));
        $metadata = $this->driver->loadMetadataForClass($refl);
        $this->assertInstanceOf(
            'Zicht\Bundle\FileManagerBundle\Metadata\PropertyMetadata',
            $metadata->propertyMetadata['file']
        );
    }
}
