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
use Zicht\Bundle\FileManagerBundle\FileManager\FileManager;

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
     * Just store the previous value, so we can check if in the bind function
     *
     * @param $event FormEvent
     */
    public function preSetData(FormEvent $event)
    {
        $data = $event->getData();

        if(!is_null($data) && is_string($data) && ! empty($data)) {

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

        if(is_null($data) && !is_null($this->previousData))
        {
            $event->setData($this->previousData);
        }
    }
}