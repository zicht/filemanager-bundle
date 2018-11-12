<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\FileManagerBundle\Metadata\Driver;

use Metadata\Driver\DriverInterface;
use Metadata\MergeableClassMetadata;
use Doctrine\Common\Annotations\Reader;
use Zicht\Bundle\FileManagerBundle\Metadata\PropertyMetadata;

/**
 * Annotation driver for @File annotations
 */
class AnnotationDriver implements DriverInterface
{
    private $reader;

    /**
     * Constructor.
     *
     * @param \Doctrine\Common\Annotations\Reader $reader
     */
    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }


    /**
     * Loads the metadata for the class.
     *
     * @param \ReflectionClass $class
     * @return \Metadata\MergeableClassMetadata
     */
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
