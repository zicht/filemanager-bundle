<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\FileManagerBundle\Doctrine;

use \Metadata\MetadataFactory;

/**
 * Contains all the metadata that is needed for the subscriber.
 */
class MetadataRegistry
{
    /**
     * Constructor.
     *
     * @param \Metadata\MetadataFactory $metadataFactory
     */
    public function __construct(MetadataFactory $metadataFactory)
    {
        $this->metadataFactory = $metadataFactory;
        $this->managedFields = array();
    }


    /**
     * Returns all field names that are managed
     *
     * @param mixed $entity
     * @return array
     */
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


    /**
     * Checks if the specified entity has any managed properties.
     *
     * @param mixed $entity
     * @return bool
     */
    public function isManaged($entity)
    {
        return count($this->getManagedFields($entity)) > 0;
    }
}