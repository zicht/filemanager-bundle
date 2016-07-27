<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\FileManagerBundle\Doctrine;

use Metadata\MetadataFactoryInterface;

/**
 * Contains all the metadata that is needed for the subscriber.
 */
class MetadataRegistry
{
    /**
     * Constructor.
     *
     * @param \Metadata\MetadataFactoryInterface $metadataFactory
     */
    public function __construct(MetadataFactoryInterface $metadataFactory)
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
            $entityClass = get_class($entity);
            $this->managedFields[$class] = array();
            do {
                $metadata = $this->metadataFactory->getMetadataForClass($entityClass);
                foreach ($metadata->propertyMetadata as $field => $metadata) {
                    if (isset($metadata->fileManager)) {
                        $this->managedFields[$class][] =$field;
                    }
                }
            } while ($entityClass = get_parent_class($entityClass));
            $this->managedFields[$class] = array_unique($this->managedFields[$class]);
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
