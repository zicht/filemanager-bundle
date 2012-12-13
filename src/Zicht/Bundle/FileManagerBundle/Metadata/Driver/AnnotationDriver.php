<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\FileManagerBundle\Metadata\Driver;

use Metadata\Driver\DriverInterface;
use Metadata\MergeableClassMetadata;
use Doctrine\Common\Annotations\Reader;
use Zicht\Bundle\FileManagerBundle\Metadata\PropertyMetadata;

class AnnotationDriver implements DriverInterface
{
    private $reader;

    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    public function loadMetadataForClass(\ReflectionClass $class)
    {
        $classMetadata = new MergeableClassMetadata($class->getName());

        foreach ($class->getProperties() as $reflectionProperty) {
            $propertyMetadata = new PropertyMetadata($class->getName(), $reflectionProperty->getName());

            /** @var $annotation \Zicht\Bundle\FileManagerBundle\Annotation\File */
            $annotation = $this->reader->getPropertyAnnotation(
                $reflectionProperty,
                'Zicht\Bundle\FileManagerBundle\Annotation\File'
            );
            if (null !== $annotation) {
                // a "@DefaultValue" annotation was found
                $propertyMetadata->fileManager = $annotation->settings;
            }

            $classMetadata->addPropertyMetadata($propertyMetadata);
        }

        return $classMetadata;
    }
}