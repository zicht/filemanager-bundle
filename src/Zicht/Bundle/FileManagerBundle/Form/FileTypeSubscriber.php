<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\FileManagerBundle\Form;

use \Symfony\Component\Form\Form;
use \Symfony\Component\Form\FormError;
use \Symfony\Component\Form\FormEvent;
use \Symfony\Component\Form\FormEvents;
use \Symfony\Component\EventDispatcher\EventSubscriberInterface;
use \Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use \Symfony\Component\HttpFoundation\File\File;
use \Symfony\Component\HttpFoundation\File\UploadedFile;
use \Zicht\Bundle\FileManagerBundle\Doctrine\PropertyHelper;
use \Zicht\Bundle\FileManagerBundle\FileManager\FileManager;
use \Zicht\Bundle\FileManagerBundle\Helper\PurgatoryHelper;
use \Symfony\Bundle\FrameworkBundle\Translation\Translator;

/**
 * Form subscriber
 *
 * This subscriber is used to keep the data preserved- which is possibly present in the FileType (when editing an entity).
 * When the form is saved (submitted) and there is no new file uploaded, the old filename is inserted in the form again.
 */
class FileTypeSubscriber implements EventSubscriberInterface
{
    private $previousData;
    private $translator;
    private $allowedFileTypes;

    /**
     * Set up the subscriber
     *
     * @param \Zicht\Bundle\FileManagerBundle\FileManager\FileManager $fileManager
     * @param string $field
     * @param \Symfony\Bundle\FrameworkBundle\Translation\Translator $translator
     * @param array $allowedFileTypes
     * @internal param string $entity
     */
    public function __construct(FileManager $fileManager, $field, Translator $translator, array $allowedFileTypes = array())
    {
        $this->fileManager      = $fileManager;
        $this->field            = $field;
        $this->translator       = $translator;
        $this->allowedFileTypes = $allowedFileTypes;
    }

    /**
     * @{inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SUBMIT    => 'preSubmit',
        );
    }

    /**
     * Just before the form is submitted, check if there is no data entered and if so, set the 'old' data back.
     *
     * @param FormEvent $event
     * @return void
     */
    public function preSubmit(FormEvent $event)
    {
        $data    = $event->getData();
        $entity = $event->getForm()->getParent()->getConfig()->getDataClass();

        //if the remove checkbox is checked, clear the data
        if (isset($data[FileType::REMOVE_FIELDNAME]) &&  $data[FileType::REMOVE_FIELDNAME] === '1') {
            $event->setData(null);
            $data = null;
            return;
        }

        //if there was a file uploaded
        if ($data !== null && is_array($data) && isset($data[FileType::UPLOAD_FIELDNAME]) && $data[FileType::UPLOAD_FIELDNAME] instanceof UploadedFile) {

            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $data[FileType::UPLOAD_FIELDNAME];
            $isValidFile  = true;

            //check if the file is allowed by the MIME-types constraint given
            if (!empty($this->allowedFileTypes) && null !== $mime = $uploadedFile->getMimeType()) {
                if (!in_array($mime, $this->allowedFileTypes)) {
                    $isValidFile = false;
                    $message     = $this->translator->trans('zicht_filemanager.wrong_type', array(
                        '%this_type%'     => $mime,
                        '%allowed_types%' => implode(', ', $this->allowedFileTypes)
                    ), $event->getForm()->getConfig()->getOption('translation_domain'));

                    $event->getForm()->addError(new FormError($message));
                    /** Set back data to old so we don`t see new file */
                    $event->setData(array(FileType::UPLOAD_FIELDNAME => $event->getForm()->getData()));
                }
            }

            if ($isValidFile) {
                $purgatoryFileManager = $this->getPurgatoryFileManager();

                $path = $purgatoryFileManager->prepare($uploadedFile, $entity, $this->field);
                $purgatoryFileManager->save($uploadedFile, $path);

                $this->prepareData($data, $path, $event->getForm()->getPropertyPath());
                $event->setData($data);
            }

        // no file was uploaded
        } else {
            $hash = $data[FileType::HASH_FIELDNAME];
            $filename = $data[FileType::FILENAME_FIELDNAME];

            // check if there was a purgatory file (and the file is not tampered with)
            if (!empty($hash) && !empty($filename)
                && PurgatoryHelper::makeHash($event->getForm()->getPropertyPath(), $filename) === $hash
            ) {
                $path = $this->getPurgatoryFileManager()->getFilePath(
                    $entity,
                    $this->field,
                    $filename
                );

                $this->prepareData($data, $path, $event->getForm()->getPropertyPath());
                $event->setData($data);

            //use the original form data, so the field isn't empty
            } elseif ($event->getForm()->getData() !== null) {

                unset($data[FileType::HASH_FIELDNAME]);
                unset($data[FileType::FILENAME_FIELDNAME]);

                $originalFormData = $event->getForm()->getData();
                $path = $this->fileManager->getFilePath($entity, $this->field, $originalFormData);

                try {
                    $file = new File($path);
                    $data[FileType::UPLOAD_FIELDNAME] = $file;
                    $event->setData($data);
                } catch (FileNotFoundException $e) {
                    //do nothing
                }
            }
        }
    }

    /**
     * This sets up the purgatoryFileManager
     * The only difference is that the rootFolder is a subfolder called 'purgatory'
     *
     * @return FileManager
     */
    private function getPurgatoryFileManager()
    {
        $purgatoryFileManager = clone $this->fileManager;
        $purgatoryFileManager->setRoot($purgatoryFileManager->getRoot() . '/purgatory');

        return $purgatoryFileManager;
    }

    /**
     * Prepares the data before sending it to the form
     *
     * @param mixed &$data
     * @param string $path
     * @param string $propertyPath
     */
    private function prepareData(&$data, $path, $propertyPath)
    {
        $file = new File($path);

        $data[FileType::FILENAME_FIELDNAME] = $file->getBasename();
        $data[FileType::HASH_FIELDNAME] = PurgatoryHelper::makeHash($propertyPath, $data[FileType::FILENAME_FIELDNAME]);

        $data[FileType::UPLOAD_FIELDNAME] = $file;
    }
}