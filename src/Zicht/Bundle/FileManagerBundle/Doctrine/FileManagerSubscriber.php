<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\FileManagerBundle\Doctrine;
use Doctrine\Common\EventSubscriber;

use Doctrine\Common\EventArgs;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\HttpFoundation\File\File;
use Metadata\MetadataFactory;

class FileManagerSubscriber implements \Doctrine\Common\EventSubscriber
{
    /**
     * @param \Zicht\Bundle\FileManagerBundle\FileManager\FileManager $fileManager
     * @param $metadataFactory
     */
    function __construct($fileManager, MetadataFactory $metadataFactory) {
        $this->fileManager = $fileManager;
        $this->metadataFactory = $metadataFactory;
        $this->scheduledForDelete = array();
    }



    public function getSubscribedEvents()
    {
        return array(
            \Doctrine\ORM\Events::prePersist,
            \Doctrine\ORM\Events::preUpdate,
            \Doctrine\ORM\Events::preRemove,
            \Doctrine\ORM\Events::postRemove,
            \Doctrine\ORM\Events::postLoad,
        );
    }


    /**
     * @param LifecycleEventArgs $eventArgs
     */
    function prePersist($eventArgs)
    {
        $this->process($eventArgs);
    }

    /**
     * @param LifecycleEventArgs $eventArgs
     */
    function preUpdate($eventArgs)
    {
        $this->process($eventArgs);
    }


    /**
     * Schedule files for deletion
     *
     * @param $eventArgs
     */
    function preRemove($eventArgs)
    {
        $entity = $eventArgs->getEntity();
        $classMetaData = $this->metadataFactory->getMetadataForClass(get_class($eventArgs->getEntity()));

        /** @var \Zicht\Bundle\FileManagerBundle\Metadata\PropertyMetadata $metadata */
        foreach ($classMetaData->propertyMetadata as $property => $metadata) {
            if (isset($metadata->fileManager)) {
                $this->scheduledForDelete[] = $this->fileManager->getFilePath($entity, $property);
            }
        }
    }


    /**
     * Remove all files that are scheduled for deletion.
     *
     * @param LifecycleEventArgs $eventArgs
     */
    function postRemove($eventArgs)
    {
        foreach ($this->scheduledForDelete as $file) {
            $this->fileManager->delete($file);
        }
    }


    /**
     *
     *
     * @param $eventArgs
     */
    function postLoad($eventArgs) {
        $entity = $eventArgs->getEntity();
        $classMetaData = $this->metadataFactory->getMetadataForClass(get_class($entity));

        /** @var \Zicht\Bundle\FileManagerBundle\Metadata\PropertyMetadata $metadata */
        foreach ($classMetaData->propertyMetadata as $property => $metadata) {
            if (isset($metadata->fileManager)) {
                $filePath = $this->fileManager->getFilePath($entity, $property);
                if ($filePath && file_exists($filePath)) {
                    PropertyHelper::setValue($entity, $property, new File($filePath));
                } else {
                    PropertyHelper::setValue($entity, $property, null);
                }
            }
        }
    }

    /**
     * Process a 'save' action (persist or update)
     *
     * @param $eventArgs
     */
    public function process($eventArgs) {
        $entity = $eventArgs->getEntity();
        $classMetaData = $this->metadataFactory->getMetadataForClass(get_class($entity));

        /** @var \Zicht\Bundle\FileManagerBundle\Metadata\PropertyMetadata $metadata */
        foreach ($classMetaData->propertyMetadata as $property => $metadata) {
            if (isset($metadata->fileManager)) {
                $newValue = $this->handle($metadata, $property, $entity);
                if ($newValue) {
                    if ($eventArgs instanceof \Doctrine\ORM\Event\PreUpdateEventArgs) {
                        $tmp = clone $entity;
                        PropertyHelper::setValue($tmp, $property, $eventArgs->getOldValue($property));
                        $this->fileManager->remove($tmp, $property);
                        $eventArgs->setNewValue($property, $newValue);
                    } else {
                        PropertyHelper::setValue($entity, $property, $newValue);
                    }
                } else {
                    if ($eventArgs instanceof \Doctrine\ORM\Event\PreUpdateEventArgs) {
                        // keep the original value, don't overwrite.
                        if ($eventArgs->hasChangedField($property)) {
                            $eventArgs->setNewValue($property, $eventArgs->getOldValue($property));
                        }
                    }
                }
            }
        }
    }


    protected function handle($metadata, $property, $entity) {
        $value = PropertyHelper::getValue($entity, $property);

        if ($value instanceof File) {
            return $this->fileManager->save($value, $entity, $property);
        }
        return null;
    }

}