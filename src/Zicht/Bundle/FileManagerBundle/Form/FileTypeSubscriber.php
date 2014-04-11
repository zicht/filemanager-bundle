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
            FormEvents::PRE_BIND          => 'bind', //this did the trick - no idea (yet) why
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
    public function bind(FormEvent $event)
    {
        $data = $event->getData();

        /** @var Form $form */
        $form = $event->getForm();

        if (null !== $data && is_array($data) && isset($data[FileType::UPLOAD_FIELDNAME]) && $data[FileType::UPLOAD_FIELDNAME] instanceof UploadedFile) {

            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $data[FileType::UPLOAD_FIELDNAME];

            $purgatoryFileManager = clone $this->fileManager;
            $purgatoryFileManager->setRoot($purgatoryFileManager->getRoot() . '/purgatory');

            $path = $purgatoryFileManager->prepare($uploadedFile, $this->entity, $this->field);
            $purgatoryFileManager->save($uploadedFile, $path);

            $data[FileType::FILENAME_FIELDNAME] = $uploadedFile->getBasename();
            $data[FileType::HASH_FIELDNAME] = PurgatoryHelper::makeHash($event->getForm()->getPropertyPath(), $data[FileType::FILENAME_FIELDNAME]);

            $data[FileType::UPLOAD_FIELDNAME] = new File($path);

            $event->setData($data);
//            $form->getParent()->get('singlePhoto')->setData(new File($path));

        } else {
            // no file was uploaded

            $hash = $form->get('hash')->getData();
            $filename = $form->get('filename')->getData();

            var_dump($hash);
            var_dump($filename);

            var_dump($data);
            exit;

            // check if there was a purgatory file (and the file is not tampered with)
            if (!empty($hash)
                && !empty($filename)
                && PurgatoryHelper::makeHash($form->getPropertyPath(), $filename)
                    === $hash
            ) {
                $purgatoryFileManager = clone $this->fileManager;
                $purgatoryFileManager->setRoot($purgatoryFileManager->getRoot() . '/purgatory');

                $filePath = $purgatoryFileManager->getFilePath(
                    $this->entity,
                    $this->field,
                    $filename
                );

                $data['filename'] = 'poep';
                $data['hash'] = 'hashendepoep';
                $data[FileType::UPLOAD_FIELDNAME] = new File($filePath);

                $event->setData($data);
            } elseif (null !== $this->previousData) {
                // use the previously data - set in preSetData()

                $data['filename'] = 'poep';
                $data['hash'] = 'hashendepoep';
                $data[FileType::UPLOAD_FIELDNAME] = $this->previousData;

                $event->setData($data);
            }
        }
    }
}