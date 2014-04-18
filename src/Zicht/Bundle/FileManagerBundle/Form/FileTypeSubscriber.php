<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\FileManagerBundle\Form;

use Symfony\Component\Form\Form;
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
            FormEvents::POST_SET_DATA => 'postSetData',
            FormEvents::PRE_BIND      => 'preBind', //this did the trick - no idea (yet) why
        );
    }

    /**
     * Just store the previous value, so we can check it in the bind function
     *
     * @param FormEvent $event
     * @return void
     */
    public function postSetData(FormEvent $event)
    {
        $data = $event->getData();

        if (!is_null($data) && is_string($data) && !empty($data)) {

            $path = $this->fileManager->getFilePath($this->entity, $this->field, $data);

            try {
                $this->previousData = new File($path);
                $event->setData($this->previousData);
            } catch (\Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException $e) {
            }
        }
    }

    /**
     * Restore the 'previous' data if available
     *
     * @param FormEvent $event
     * @return void
     */
    public function preBind(FormEvent $event)
    {
        $data = $event->getData();
        
        if (null !== $data && is_array($data) && isset($data[FileType::UPLOAD_FIELDNAME]) && $data[FileType::UPLOAD_FIELDNAME] instanceof UploadedFile) {

            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $data[FileType::UPLOAD_FIELDNAME];

            $purgatoryFileManager = $this->getPurgatoryFileManager();

            $path = $purgatoryFileManager->prepare($uploadedFile, $this->entity, $this->field);
            $purgatoryFileManager->save($uploadedFile, $path);

            $this->prepareData($data, $path, $event->getForm()->getPropertyPath());
            $event->setData($data);
        }
        else { // no file was uploaded

            $hash = $data[FileType::HASH_FIELDNAME];
            $filename = $data[FileType::FILENAME_FIELDNAME];

            // check if there was a purgatory file (and the file is not tampered with)
            if (!empty($hash) && !empty($filename)
                && PurgatoryHelper::makeHash($event->getForm()->getPropertyPath(), $filename) === $hash
            ) {
                $path = $this->getPurgatoryFileManager()->getFilePath(
                    $this->entity,
                    $this->field,
                    $filename
                );

                $this->prepareData($data, $path, $event->getForm()->getPropertyPath());
                $event->setData($data);
            }
            elseif (null !== $this->previousData) {

                // use the previously data - set in preSetData()

                unset($data[FileType::HASH_FIELDNAME]);
                unset($data[FileType::FILENAME_FIELDNAME]);

                $data[FileType::UPLOAD_FIELDNAME] = $this->previousData;
                $event->setData($data);
            }
        }
    }

    private function getPurgatoryFileManager()
    {
        $purgatoryFileManager = clone $this->fileManager;
        $purgatoryFileManager->setRoot($purgatoryFileManager->getRoot() . '/purgatory');

        return $purgatoryFileManager;
    }

    private function prepareData(&$data, $path, $propertyPath)
    {
        $file = new File($path);

        $data[FileType::FILENAME_FIELDNAME] = $file->getBasename();
        $data[FileType::HASH_FIELDNAME] = PurgatoryHelper::makeHash($propertyPath, $data[FileType::FILENAME_FIELDNAME]);

        $data[FileType::UPLOAD_FIELDNAME] = $file;
    }
}