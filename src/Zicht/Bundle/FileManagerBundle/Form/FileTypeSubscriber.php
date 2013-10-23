<?php
/**
 * @author Oskar van Velden <oskar@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
  */

namespace Zicht\Bundle\FileManagerBundle\Form;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Zicht\Bundle\FileManagerBundle\Doctrine\PropertyHelper;
use Zicht\Bundle\FileManagerBundle\FileManager\FileManager;
use Zicht\Bundle\FileManagerBundle\Helper\PurgatoryHelper;

class FileTypeSubscriber implements EventSubscriberInterface
{
    private $previousData;

    function __construct(FileManager $fileManager, $entity, $field)
    {
        $this->fileManager = $fileManager;
        $this->entity = $entity;
        $this->field  = $field;
    }

    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::POST_SET_DATA => 'preSetData',
            FormEvents::BIND => 'bind',
        );
    }

    /**
     * Just store the previous value, so we can check it in the bind function
     *
     * @param $event FormEvent
     */
    public function preSetData(FormEvent $event)
    {
        $data = $event->getData();

        if( ! is_null($data) && is_string($data) && ! empty($data)) {

            $path = $this->fileManager->getFilePath($this->entity, $this->field, $data);

            $this->previousData = new File($path);
            $event->setData($this->previousData);
        }
    }

    /**
     * @param $event FormEvent
     */
    public function bind(FormEvent $event)
    {
        $data = $event->getData();

        if( ! is_null($data) ) { //use the new uploaded file

            if ($data instanceof UploadedFile) {

                $purgatoryFileManager = clone $this->fileManager;
                $purgatoryFileManager->setRoot($purgatoryFileManager->getRoot() . '/purgatory');

                $path = $purgatoryFileManager->prepare($data, $this->entity, $this->field);
                $purgatoryFileManager->save($data, $path);

                $event->setData(new File($path));
            }
        } else { //no file uploaded

            $postfix = PurgatoryHelper::makePostFix($this->entity, $this->field);
            $file_hash = PurgatoryHelper::makeHash($this->entity, $this->field, $_POST['purgatory_file_filename_' . $postfix]);

            //check if there was a purgatory file (and the file is not tampered with)
            if( isset($_POST['purgatory_file_filename_' . $postfix])
             && isset($_POST['purgatory_file_hash_' . $postfix])
             && $file_hash === $_POST['purgatory_file_hash_' . $postfix]) {

                $purgatoryFileManager = clone $this->fileManager;
                $purgatoryFileManager->setRoot($purgatoryFileManager->getRoot() . '/purgatory');

                $file = new File($purgatoryFileManager->getFilePath($this->entity, $this->field, $_POST['purgatory_file_filename_' . $postfix]));
                $event->setData($file);

            }
            elseif( !is_null($this->previousData) ) { //use the previously data - set in preSetData()

                $event->setData($this->previousData);
            }

        }
    }
}