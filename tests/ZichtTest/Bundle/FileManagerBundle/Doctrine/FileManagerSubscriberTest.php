<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace ZichtTest\Bundle\FileManagerBundle\Doctrine;

class MyModel
{
    public $value = null;

    public function setFoo($value)
    {
        $this->value = $value;
    }
}

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
                ->setMethods(array('prepare', 'getFilePath'))
                ->getMock();
        }
        if (null == $metadata) {
            $metadata  = $this->getMockBuilder('Zicht\Bundle\FileManagerBundle\Doctrine\MetadataRegistry')
                ->setMethods(array('getManagedFields'))
                ->disableOriginalConstructor()
                ->getMock();
        }

        $this->fm = $fm;
        $this->metadata = $metadata;

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


    function testPreUpdateWillSetBasenameOnModel()
    {
        $subscriber = $this->getSubscriber();
        $object = new MyModel();
        $file = $this->getMockBuilder('Symfony\Component\HttpFoundation\File\File')->disableOriginalConstructor()->getMock();
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        $values = array('foo' => array(null, $file));
        $event = new \Doctrine\Common\Persistence\Event\PreUpdateEventArgs($object, $em, $values);
        $this->metadata->expects($this->once())->method('getManagedFields')->will($this->returnValue(array('foo')));
        $this->fm->expects($this->once())->method('prepare')->with($file)->will($this->returnValue('/tmp/foo/somefile.bar'));
        $subscriber->preUpdate($event);
        $this->assertEquals('somefile.bar', $object->value);
    }


    function testPreUpdateWillScheduleDeleteOfPreviousValue()
    {
        $subscriber = $this->getSubscriber();
        $object = new MyModel();
        $object->setFoo('some previous value');
        $file = $this->getMockBuilder('Symfony\Component\HttpFoundation\File\File')->disableOriginalConstructor()->getMock();
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        $values = array('foo' => array($object->value, $file));
        $event = new \Doctrine\Common\Persistence\Event\PreUpdateEventArgs($object, $em, $values);
        $this->metadata->expects($this->once())->method('getManagedFields')->will($this->returnValue(array('foo')));
        $this->fm->expects($this->once())->method('prepare')->with($file)->will($this->returnValue('/tmp/foo/somefile.bar'));
        $this->fm->expects($this->once())->method('getFilePath')->with($object, 'foo', 'some previous value')->will($this->returnValue('/tmp/old value.bar'));
        $subscriber->preUpdate($event);
        $this->assertEquals('somefile.bar', $object->value);
    }


    function testPreUpdateWillKeepOldValueIfChangesetContainsNull()
    {
        $subscriber = $this->getSubscriber();
        $object = new MyModel();
        $object->setFoo('some previous value');

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        $values = array('foo' => array($object->value, null));
        $event = new \Doctrine\Common\Persistence\Event\PreUpdateEventArgs($object, $em, $values);
        $this->metadata->expects($this->once())->method('getManagedFields')->will($this->returnValue(array('foo')));
        $subscriber->preUpdate($event);
        $this->assertEquals('some previous value', $object->value);
    }
}