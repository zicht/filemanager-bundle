<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace ZichtTest\Bundle\FileManagerBundle\Integrity;

/**
 * @property \Zicht\Bundle\FileManagerBundle\FileManager\FileManagerr $fm
 * @property \Metadata\MetaFactoryInterface $mf
 */
abstract class AbstractCheckerTestCase extends \PHPUnit_Framework_TestCase
{
    function setUp()
    {
        $this->fm = $this->getMockBuilder('Zicht\Bundle\FileManagerBundle\FileManager\FileManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mf = $this->getMock('Metadata\MetadataFactoryInterface');
        $this->doctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->setMethods(array('getClassName', 'findAll'))
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->repo->expects($this->any())->method('getClassName')->with()->will($this->returnValue('Ns\Foo'));
        $this->doctrine->expects($this->any())->method('getRepository')->with('Foo')->will(
            $this->returnValue($this->repo)
        );
        $this->records = array(
            $this->getMockEntity(''),
            $this->getMockEntity('foo'),
            $this->getMockEntity('bar'),
        );
        $this->repo->expects($this->any())->method('findAll')->will(
            $this->returnValue(
                $this->records
            )
        );
        $this->metadata = (object) array(
            'propertyMetadata' => array(
                'file' => (object) array(
                    'fileManager' => true
                ),
                'someotherprop' => (object) array()
            )
        );
        // TODO mock out fs with FileSystem
        @mkdir('/tmp/checker-test');
        $this->fm->expects($this->any())->method('getDir')->will($this->returnValue('/tmp/checker-test'));
        $this->mf->expects($this->any())->method('getMetadataForClass')->with('Ns\Foo')->will(
            $this->returnValue($this->metadata)
        );
    }


    function tearDown()
    {
        // TODO mock out fs with FileSystem
        shell_exec('rm -rf /tmp/checker-test');
    }


    protected function getMockEntity($fileValue)
    {
        $ret = $this->getMockBuilder('stdClass')
            ->setMethods(array('getFile', 'setFile'))
            ->getMock()
        ;
        $ret->expects($this->any())->method('getFile')->will($this->returnValue($fileValue));
        return $ret;
    }
}