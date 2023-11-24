<?php

namespace Zicht\Bundle\FileManagerBundle\Metadata\Driver;

use Metadata\ClassMetadata;
use Metadata\Driver\DriverInterface;
use Metadata\MergeableClassMetadata;
use Doctrine\Common\Annotations\Reader;
use Zicht\Bundle\FileManagerBundle\Annotation\File;
use Zicht\Bundle\FileManagerBundle\Metadata\PropertyMetadata;

/**
 * Attribute driver for {@see File} attributes
 */
class AttributeDriver implements DriverInterface
{
    public function loadMetadataForClass(\ReflectionClass $class): ?ClassMetadata
    {
        $classMetadata = new MergeableClassMetadata($class->getName());

        foreach ($class->getProperties() as $reflectionProperty) {
            if (0 === count($attributes = $reflectionProperty->getAttributes(File::class))) {
                continue;
            }

            foreach ($attributes as $attribute) {
                /** @var File $file */
                $file = $attribute->newInstance();
                $propertyMetadata = new PropertyMetadata($class->getName(), $reflectionProperty->getName());
                $propertyMetadata->fileManager = $file->settings;
                $classMetadata->addPropertyMetadata($propertyMetadata);
                break;
            }

        }

        return $classMetadata;
    }
}
