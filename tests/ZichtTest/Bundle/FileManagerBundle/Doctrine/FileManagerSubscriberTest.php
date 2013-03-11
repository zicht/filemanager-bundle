<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace ZichtTest\Bundle\FileManagerBundle\Doctrine;

/**
 * @covers Zicht\Bundle\FileManagerBundle\Doctrine\FileManagerSubscriber
 */
class FileManagerSubscriberTest extends \PHPUnit_Framework_TestCase
{
    protected $fmSubscriber;

    protected function getSubscriber($fm = null, $metadata = null)
    {
        if (null == $fm) {
            $fm = $this->getMockBuilder('Zicht\Bundle\FileManagerBundle\FileManager')
                ->getMock();
        }
        if (null == $metadata) {
            $metadata  = $this->getMockBuilder('Zicht\Bundle\FileManagerBundle\Doctrine\MetadataRegistry')
                ->disableOriginalConstructor()
                ->getMock();
        }

        return new \Zicht\Bundle\FileManagerBundle\Doctrine\FileManagerSubscriber(
            $fm,
            $metadata
        );
    }

    function testGetSubscribedEvents() {
        $subscriber = $this->getSubscriber();
        foreach ($subscriber->getSubscribedEvents() as $eventName) {
            $this->assertTrue(is_callable(array($subscriber, $eventName)));
        }
    }
}

