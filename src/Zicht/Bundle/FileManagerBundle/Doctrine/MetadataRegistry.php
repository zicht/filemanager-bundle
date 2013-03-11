<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\FileManagerBundle\Doctrine;

use Metadata\MetadataFactory;

class MetadataRegistry
{
    public function __construct(MetadataFactory $metadataFactory)
    {
        $this->metadataFactory = $metadataFactory;
        $this->managedFields = array();
    }


    public function getManagedFields($entity)
    {
        $class = get_class($entity);

        if (!isset($this->managedFields[$class])) {
            $metadata = $this->metadataFactory->getMetadataForClass(get_class($entity));
            $this->managedFields[$class] = array();
            foreach ($metadata->propertyMetadata as $field => $metadata) {
                if (isset($metadata->fileManager)) {
                    $this->managedFields[$class][] =$field;
                }
            }
        }
        return $this->managedFields[$class];
    }


    public function isManaged($entity)
    {
        return count($this->getManagedFields($entity)) > 0;
    }
}