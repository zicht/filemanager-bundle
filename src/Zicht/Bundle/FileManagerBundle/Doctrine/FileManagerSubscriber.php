<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\FileManagerBundle\Doctrine;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

use Symfony\Component\HttpFoundation\File\File;

use Zicht\Bundle\FileManagerBundle\FileManager\FileManager;
use Zicht\Bundle\FileManagerBundle\FixtureFile;

/**
 * The subscriber that manages updates, persists and deletes of managed file properties.
 */
class FileManagerSubscriber implements EventSubscriber
{
    /**
     * Constructor.
     *
     * @param FileManager $fileManager
     * @param MetadataRegistry $metadata
     */
    public function __construct($fileManager, MetadataRegistry $metadata)
    {
        $this->fileManager = $fileManager;
        $this->metadata = $metadata;
        $this->managedFields = array();
        $this->unitOfWork = array();
    }


    /**
     * Returns the subscribed events for this listener.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            'preUpdate',
            'preRemove',
            'prePersist',
            'postPersist',
            'postUpdate',
            'postRemove',
        );
    }


    /**
     * Replaces a file value and removes the old file.
     *
     * @param PreUpdateEventArgs $eventArgs
     * @return void
     */
    public function preUpdate($eventArgs)
    {
        $entity = $eventArgs->getEntity();
        $changeset = $eventArgs->getEntityChangeSet();

        foreach ($this->metadata->getManagedFields($entity) as $field) {
            if (isset($changeset[$field])) {
                list($old, $new) = $changeset[$field];

                if ($old) {
                    $tempFilePath = $this->fileManager->getFilePath($entity, $field, $old);

                    if ($new && ((string)$new) && (string)$new == $tempFilePath) {
                        /** @var File $new */
                        // in this case the file was wrapped in a file object, but not actually changed.
                        // We "unwrap" the value here.
                        $new = $new->getBasename();
                    } else {
                        $filepath = $this->fileManager->getFilePath($entity, $field, $old);
                        $this->unitOfWork[spl_object_hash($entity)][$field]['delete'] =
                            function (FileManager $fm) use ($filepath) {
                                $fm->delete($filepath);
                            };
                    }
                }

                if (is_string($new)) {
                    $eventArgs->setNewValue($field, $new);
                } else {
                    if (null !== $new) {
                        $eventArgs->setNewValue($field, $this->scheduleForUpload($new, $entity, $field));
                    }
                }
            }
        }
    }


    /**
     * Removes the files attached to the entity
     *
     * @param LifeCycleEventArgs $eventArgs
     * @return void
     */
    public function preRemove($eventArgs)
    {
        $entity = $eventArgs->getEntity();
        foreach ($this->metadata->getManagedFields($entity) as $field) {
            $file = PropertyHelper::getValue($entity, $field);

            if ($file) {
                $filepath = $this->fileManager->getFilePath($entity, $field, $file);

                $this->unitOfWork[spl_object_hash($entity)][$field]['delete'] = function (FileManager $fm) use ($filepath) {
                    $fm->delete($filepath);
                };
            }
        }
    }


    /**
     * Saves the file(s) to disk
     *
     * @param LifecycleEventArgs $eventArgs
     * @return void
     */
    public function prePersist($eventArgs)
    {
        $entity = $eventArgs->getEntity();

        foreach ($this->metadata->getManagedFields($entity) as $field) {
            $value = PropertyHelper::getValue($entity, $field);
            if (null !== $value) {
                $this->scheduleForUpload($value, $entity, $field);
            }
        }
    }


    /**
     * Puts the upload in the unit of work to be executed when the flush is done.
     *
     * @param mixed $value
     * @param mixed $entity
     * @param string $field
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function scheduleForUpload($value, $entity, $field)
    {
        if (is_string($value)) {
            // try to locate the file on disk
            $value = new FixtureFile($this->fileManager->getFilePath($entity, $field, $value));
        }

        if ($value instanceof File) {
            $path = $this->fileManager->prepare($value, $entity, $field);
            $fileName = basename($path);
            PropertyHelper::setValue($entity, $field, $fileName);
            $this->unitOfWork[spl_object_hash($entity)][$field]['save'] = function ($fm) use ($value, $path) {
                $fm->save($value, $path);
            };
            return $fileName;
        } else {
            throw new \InvalidArgumentException("Invalid argument to scheduleForUpload(): " . gettype($value));
        }
    }


    /**
     * Trigger the unit of work to be executed.
     *
     * @return void
     */
    public function postUpdate()
    {
        $this->doFlush();
    }


    /**
     * Trigger the unit of work to be executed.
     *
     * @return void
     */
    public function postPersist()
    {
        $this->doFlush();
    }


    /**
     * Trigger the unit of work to be executed.
     *
     * @return void
     */
    public function postRemove ()
    {
        $this->doFlush();
    }


    /**
     * Executes all scheduled callbacks in the unit of work.
     *
     * @return void
     */
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
