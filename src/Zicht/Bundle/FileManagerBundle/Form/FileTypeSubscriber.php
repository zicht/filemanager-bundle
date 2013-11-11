<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\FileManagerBundle\Form;

use \Symfony\Component\Form\FormEvent;
use \Symfony\Component\Form\FormEvents;
use \Symfony\Component\EventDispatcher\EventSubscriberInterface;
use \Symfony\Component\HttpFoundation\File\File;
use \Symfony\Component\HttpFoundation\File\UploadedFile;
use \Zicht\Bundle\FileManagerBundle\Doctrine\PropertyHelper;
use \Zicht\Bundle\FileManagerBundle\FileManager\FileManager;
use \Zicht\Bundle\FileManagerBundle\Helper\PurgatoryHelper;

/**
 * Form subscriber
 */
class FileTypeSubscriber implements EventSubscriberInterface
{
    private $previousData;

    /**
     * Set up the subscriber
     *
     * @param \Zicht\Bundle\FileManagerBundle\FileManager\FileManager $fileManager
     * @param string $entity
     * @param string $field
     */
    public function __construct(FileManager $fileManager, $entity, $field)
    {
        $this->fileManager = $fileManager;
        $this->entity      = $entity;
        $this->field       = $field;
    }

    /**
     * @{inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::POST_SET_DATA => 'preSetData',
            FormEvents::BIND          => 'bind',
        );
    }

    /**
     * Just store the previous value, so we can check it in the bind function
     *
     * @param FormEvent $event
     * @return void
     */
    public function preSetData(FormEvent $event)
    {
        $data = $event->getData();

        if (!is_null($data) && is_string($data) && !empty($data)) {

            $path = $this->fileManager->getFilePath($this->entity, $this->field, $data);

            $this->previousData = new File($path);
            $event->setData($this->previousData);
        }
    }

    /**
     * Restore the 'previous' data if available
     *
     * @param FormEvent $event
     * @return void
     */
    public function bind(FormEvent $event)
    {
        $data = $event->getData();

        if (null !== $data && $data instanceof UploadedFile) {
            $purgatoryFileManager = clone $this->fileManager;
            $purgatoryFileManager->setRoot($purgatoryFileManager->getRoot() . '/purgatory');

            $path = $purgatoryFileManager->prepare($data, $this->entity, $this->field);
            $purgatoryFileManager->save($data, $path);

            $event->setData(new File($path));
        } else {
            // no file was uploaded

            $postfix = PurgatoryHelper::makePostFix($this->entity, $this->field);

            // check if there was a purgatory file (and the file is not tampered with)
            if (isset($_POST['purgatory_file_filename_' . $postfix])
                && isset($_POST['purgatory_file_hash_' . $postfix])
                && PurgatoryHelper::makeHash($this->entity, $this->field, $_POST['purgatory_file_filename_' . $postfix])
                    === $_POST['purgatory_file_hash_' . $postfix]
            ) {
                $purgatoryFileManager = clone $this->fileManager;
                $purgatoryFileManager->setRoot($purgatoryFileManager->getRoot() . '/purgatory');

                $filePath = $purgatoryFileManager->getFilePath(
                    $this->entity,
                    $this->field,
                    $_POST['purgatory_file_filename_' . $postfix]
                );

                $file = new File($filePath);
                $event->setData($file);
            } elseif (null !== $this->previousData) {
                // use the previously data - set in preSetData()
                $event->setData($this->previousData);
            }
        }
    }
}