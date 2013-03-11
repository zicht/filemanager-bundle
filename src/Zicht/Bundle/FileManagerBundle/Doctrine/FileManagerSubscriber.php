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
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Metadata\MetadataFactory;
use Zicht\Bundle\FileManagerBundle\FileManager\FileManager;
use Doctrine\ORM\Events;

class FileManagerSubscriber implements \Doctrine\Common\EventSubscriber
{
    /**
     * @param \Zicht\Bundle\FileManagerBundle\FileManager\FileManager $fileManager
     * @param $metadata
     */
    function __construct($fileManager, MetadataRegistry $metadata) {
        $this->fileManager = $fileManager;
        $this->metadata = $metadata;
        $this->managedFields = array();
        $this->unitOfWork = array();
    }


    public function getSubscribedEvents()
    {
        return array(
            Events::preUpdate,
            Events::preRemove,
            Events::prePersist,
            Events::postPersist,
            Events::postUpdate,
            Events::postRemove,
        );
    }


    /**
     * @param \Doctrine\ORM\Event\PreUpdateEventArgs $eventArgs
     */
    function preUpdate($eventArgs)
    {
        $entity = $eventArgs->getEntity();
        $changeset = $eventArgs->getEntityChangeSet();

        foreach ($this->metadata->getManagedFields($entity) as $field) {
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
        foreach ($this->metadata->getManagedFields($eventArgs->getEntity()) as $field) {
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

        foreach ($this->metadata->getManagedFields($entity) as $field) {
            $value = PropertyHelper::getValue($entity, $field);
            if (null !== $value) {
                $this->scheduleForUpload($value, $entity, $field);
            }
        }
    }


    public function scheduleForUpload($value, $entity, $field)
    {
        if ($value instanceof File) {
            if ($value instanceof UploadedFile && !$value->getError()) {
                $path = $this->fileManager->prepare($value, $entity, $field);
                $fileName = basename($path);
                PropertyHelper::setValue($entity, $field, $fileName);
                $this->unitOfWork[spl_object_hash($entity)][$field]['save'] = function($fm) use($value, $path) {
                    $fm->save($value, $path);
                };
                return $fileName;
            } else {
                return $value->getBasename();
            }
        } else {
            throw new \InvalidArgumentException("Invalid argument to scheduleForUpload(): " . gettype($value));
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