<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
  */

namespace ZichtTest\Bundle\FileManagerBundle\Form\Transformer;

use Symfony\Component\Form\FormEvents;

class FileTypeSubscriberTestEntity
{
    public $foo;
}

class FileTypeSubscriberTest extends \PHPUnit_Framework_TestCase
{
    function setUp()
    {
        $this->fm = $this->getMockBuilder('Zicht\Bundle\FileManagerBundle\FileManager\FileManager')
            ->setMethods(array('getFilePath'))
            ->disableOriginalConstructor()
            ->getMock();
    }

    function testSubscriberCreation()
    {
        $entity = new FileTypeSubscriberTestEntity();
        $field  = 'inputField';
        $subscriber = new \Zicht\Bundle\FileManagerBundle\Form\FileTypeSubscriber($this->fm, $entity, $field);
    }

    function testGetEventsToGetFullCoverageHarHarHar()
    {
        $events = \Zicht\Bundle\FileManagerBundle\Form\FileTypeSubscriber::getSubscribedEvents();

        $this->assertTrue(is_array($events));
        $this->assertArrayHasKey(FormEvents::POST_SET_DATA, $events);
        $this->assertArrayHasKey(FormEvents::SUBMIT, $events);
    }


}
