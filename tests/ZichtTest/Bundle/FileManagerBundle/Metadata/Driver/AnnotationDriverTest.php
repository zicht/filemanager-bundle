<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace ZichtTest\Bundle\FileManagerBundle\Metadata;

use Zicht\Bundle\FileManagerBundle\Annotation\File;


class MyClass
{
    public $file;
    public $noFile;
}


class AnnotationDriverTest extends \PHPUnit_Framework_TestCase
{
    function setUp()
    {
        $this->reader = $this->getMockBuilder('Doctrine\Common\Annotations\Reader')
            ->disableOriginalConstructor()
            ->getMock();

        $this->driver = new \Zicht\Bundle\FileManagerBundle\Metadata\Driver\AnnotationDriver($this->reader);
    }

    function testLoadMetadataForClass()
    {
        $refl = new \ReflectionClass(__NAMESPACE__ . '\\MyClass');
        $prop1 = $refl->getProperty('file');
        $prop2 = $refl->getProperty('noFile');
        $this->reader->expects($this->at(0))->method('getPropertyAnnotation')->with($prop1)->will($this->returnValue(
            new File(array())
        ));
        $metadata = $this->driver->loadMetadataForClass($refl);
        $this->assertInstanceOf(
            'Zicht\Bundle\FileManagerBundle\Metadata\PropertyMetadata',
            $metadata->propertyMetadata['file']
        );
    }
}