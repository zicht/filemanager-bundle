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
use Zicht\Bundle\FileManagerBundle\FileManager\FileManager;

class FileManagerSubscriber implements \Doctrine\Common\EventSubscriber
{
    /**
     * @param \Zicht\Bundle\FileManagerBundle\FileManager\FileManager $fileManager
     * @param $metadataFactory
     */
    function __construct($fileManager, MetadataFactory $metadataFactory) {
        $this->fileManager = $fileManager;
        $this->metadataFactory = $metadataFactory;
        $this->managedFields = array();
        $this->unitOfWork = array();
    }


    protected function getManagedFields($entity)
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


    protected function isManaged($entity)
    {
        return count($this->getManagedFields($entity)) > 0;
    }


    public function getSubscribedEvents()
    {
        return array(
            \Doctrine\ORM\Events::preUpdate,
            \Doctrine\ORM\Events::preRemove,
            \Doctrine\ORM\Events::postLoad,
            \Doctrine\ORM\Events::prePersist,
            \Doctrine\ORM\Events::postPersist,
            \Doctrine\ORM\Events::postUpdate,
            \Doctrine\ORM\Events::postRemove,
        );
    }


    /**
     * @param \Doctrine\ORM\Event\PreUpdateEventArgs $eventArgs
     */
    function preUpdate($eventArgs)
    {
        $entity = $eventArgs->getEntity();
        $changeset = $eventArgs->getEntityChangeSet();

        foreach ($this->getManagedFields($entity) as $field) {
            if (isset($changeset[$field])) {
                list($old, $new) = $changeset[$field];

                if ($new === null) {
                    $eventArgs->setNewValue($field, $old);
                } else {
                    if ($old) {
                        $filepath = $this->fileManager->getFilePath($entity, $field, $old);
                        $this->unitOfWork[spl_object_hash($entity)][$field]['delete'] = function(FileManager $fm) use($filepath) {
                            $fm->delete($filepath);
                        };
                    }
                    $eventArgs->setNewValue($field, $this->scheduleForUpload($new, $entity, $field));
                }
            }
        }
    }


    /**
     * @param LifeCycleEventArgs
     */
    function preRemove($eventArgs)
    {
        foreach ($this->getManagedFields($eventArgs->getEntity()) as $field) {
            $file = PropertyHelper::getValue($eventArgs->getEntity(), $field);

            if ($file) {
                $this->unitOfWork[]= function(FileManager $fm) use($file) {
                    $fm->delete($file);
                };
            }
        }
    }


    function prePersist($eventArgs)
    {
        $entity = $eventArgs->getEntity();

        foreach ($this->getManagedFields($entity) as $field) {
            $value = PropertyHelper::getValue($entity, $field);
            if (null !== $value) {
                $this->scheduleForUpload($value, $entity, $field);
            }
        }
    }


    public function scheduleForUpload($value, $entity, $field)
    {
        if ($value instanceof File) {
            $path = $this->fileManager->prepare($value, $entity, $field);
            $fileName = basename($path);
            PropertyHelper::setValue($entity, $field, $fileName);
            $this->unitOfWork[spl_object_hash($entity)][$field]['save'] = function($fm) use($value, $path) {
                $fm->save($value, $path);
            };
            return $fileName;
        } else {
            throw new \InvalidArgumentException("Invalid argument to scheduleForUpload(): " . gettype($value));
        }
    }


    function postLoad($eventArgs)
    {
        $entity = $eventArgs->getEntity();

        foreach ($this->getManagedFields($entity) as $field) {
            $filePath = $this->fileManager->getFilePath($entity, $field);
            if ($filePath && file_exists($filePath)) {
                PropertyHelper::setValue($entity, $field, new File($filePath));
            } else {
                PropertyHelper::setValue($entity, $field, null);
            }
        }
    }


    function postUpdate()
    {
        $this->doFlush();
    }


    function postPersist()
    {
        $this->doFlush();
    }


    function postRemove ()
    {
        $this->doFlush();
    }


    public function doFlush()
    {
        while ($unit = array_shift($this->unitOfWork)) {
            while ($operations = array_shift($unit)) {
                while ($callback = array_shift($operations)) {
                    call_user_func($callback, $this->fileManager);
                }
            }
        }
    }
}