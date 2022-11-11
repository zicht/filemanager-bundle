<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace ZichtTest\Bundle\FileManagerBundle\FileManager;

use PHPUnit\Framework\TestCase;
use Zicht\Bundle\FileManagerBundle\DependencyInjection\ZichtFileManagerExtension;
use Zicht\Bundle\FileManagerBundle\Mapping\NamingStrategyInterface;

class SomeEntity
{
    public $someField;

    public function setSomeField($someField)
    {
        $this->someField = $someField;
    }

    public function getSomeField()
    {
        return $this->someField;
    }
}

class FileManagerTest extends TestCase
{
    /**
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    protected $filesystem;

    /**
     * @var \Zicht\Bundle\FileManagerBundle\FileManager\FileManager
     */
    protected $fm;

    public function setUp(): void
    {
        $this->filesystem = $this->getMockBuilder('Symfony\Component\Filesystem\Filesystem')->setMethods(['exists', 'mkdir', 'touch', 'remove'])->disableOriginalConstructor()->getMock();

        $naming = $this->getMockBuilder(NamingStrategyInterface::class)->getMock();
        $naming->method('normalize')->willReturn('foo.png');

        $this->fm = new \Zicht\Bundle\FileManagerBundle\FileManager\FileManager(
            $this->filesystem,
            '/media/',
            'http://assets/',
            $naming
        );
    }


    function testPrepareWillStubFile()
    {
        $e = new SomeEntity();
        $file = new \Symfony\Component\HttpFoundation\File\File('/tmp/foo.png', false);
        $this->filesystem->expects($this->once())->method('mkdir')->with('/media/someentity/someField')->will($this->returnValue(true));
        $this->filesystem->expects($this->once())->method('exists')->with('/media/someentity/someField/foo.png')->will($this->returnValue(false));
        $this->filesystem->expects($this->once())->method('touch')->with('/media/someentity/someField/foo.png');

        $this->fm->prepare(
            $file,
            $e,
            'someField'
        );
    }

    function testPrepareWillStubFileWithSuffixIfFirstOneExists()
    {
        $naming = $this->getMockBuilder(NamingStrategyInterface::class)->getMock();
        $naming->expects($this->exactly(2))->method('normalize')->willReturnOnConsecutiveCalls('foo.png', 'foo-1.png');

        $fm = new \Zicht\Bundle\FileManagerBundle\FileManager\FileManager(
            $this->filesystem,
            '/media/',
            'http://assets/',
            $naming
        );

        $e = new SomeEntity();
        $file = new \Symfony\Component\HttpFoundation\File\File('/tmp/foo.png', false);
        $this->filesystem->expects($this->once())->method('mkdir')->with('/media/someentity/someField')->will($this->returnValue(true));
        $this->filesystem->expects($this->exactly(2))->method('exists')
            ->withConsecutive(['/media/someentity/someField/foo.png'], ['/media/someentity/someField/foo-1.png'])
            ->willReturnOnConsecutiveCalls($this->returnValue(true), $this->returnValue(false));
        $this->filesystem->expects($this->once())->method('touch')->with('/media/someentity/someField/foo-1.png');

        $fm->prepare(
            $file,
            $e,
            'someField'
        );
    }


    function testDestructorWillRemoveStubsIfNotSaved()
    {
        $e = new SomeEntity();
        $file = new \Symfony\Component\HttpFoundation\File\File('/tmp/foo.png', false);
        $this->filesystem->expects($this->once())->method('touch')->with('/media/someentity/someField/foo.png');
        $this->filesystem->expects($this->once())->method('remove')->with('/media/someentity/someField/foo.png');

        $this->fm->prepare($file, $e, 'someField');
        $this->fm->__destruct();
    }


    function testDestructorWillNotRemoveStubsIfSaved()
    {
        $e = new SomeEntity();
        $file = $this->getMockBuilder('Symfony\Component\HttpFoundation\File\File')->disableOriginalConstructor()->getMock();
        $this->filesystem->expects($this->once())->method('touch')->with('/media/someentity/someField/foo.png');
        $this->filesystem->expects($this->once())->method('remove')->with('/media/someentity/someField/foo.png');

        $path = $this->fm->prepare($file, $e, 'someField');
        $file->expects($this->once())->method('move')->with(dirname($path), basename($path));

        $this->fm->save($file, $path);
        $this->fm->__destruct();
    }


    function testSaveIsGuardedByPreparedPathsCheck()
    {
        $this->expectException('\RuntimeException');
        $file = new \Symfony\Component\HttpFoundation\File\File('/tmp/foo', false);
        $this->fm->save($file, '/etc/passwd');
    }


    function testProposeFileName()
    {
        $file = new \Symfony\Component\HttpFoundation\File\File('/foo/bar.png', false);
        $fn = $this->fm->proposeFilename(
            $file,
            123
        );
        $this->assertEquals('bar-123.png', $fn);
    }

    function testProposeFileNameWillUseClientNameIfUploadedFile()
    {
        $file = $this->getMockBuilder('Symfony\Component\HttpFoundation\File\UploadedFile')
            ->setMethods(['getClientOriginalName'])
            ->disableOriginalConstructor()
            ->getMock();
        $file->expects($this->once())->method('getClientOriginalName')->will($this->returnValue('Some exotic name.png'));
        $fn = $this->fm->proposeFilename($file, 123);
        $this->assertEquals('some-exotic-name-123.png', $fn);
    }


    function testDeleteWillThrowExceptionOnFileSmell()
    {
        $this->expectException('\RuntimeException');
        $this->filesystem->expects($this->never())->method('remove');
        $this->fm->delete('/etc/passwd');
    }


    function testDelete()
    {
        $this->filesystem->expects($this->once())->method('exists')->with('/media/foo')->will($this->returnValue(true));
        $this->filesystem->expects($this->once())->method('remove')->with('/media/foo');
        $this->assertTrue($this->fm->delete('/media/foo'));
    }

    function testDeleteWillReturnFalseIfFileDoesNotExist()
    {
        $this->filesystem->expects($this->once())->method('exists')->with('/media/foo')->will($this->returnValue(false));
        $this->filesystem->expects($this->never())->method('remove');
        $this->assertFalse($this->fm->delete('/media/foo'));
    }


    function testGetDir()
    {
        $this->assertEquals(
            '/media/someentity/someField',
            $this->fm->getDir(new SomeEntity(), 'someField')
        );
    }

    function testGetFileUrl()
    {
        $e = new SomeEntity;
        $e->someField = 'foo.png';
        $this->assertEquals(
            'http://assets/someentity/someField/foo.png',
            $this->fm->getFileUrl($e, 'someField')
        );
    }

    function testGetFileUrlWithSpecifiedFile()
    {
        $e = new SomeEntity;
        $e->someField = 'foo.png';
        $this->assertEquals(
            'http://assets/someentity/someField/bar.png',
            $this->fm->getFileUrl($e, 'someField', 'bar.png')
        );
    }

    function testGetFileUrlWithFileObject()
    {
        $e = new SomeEntity;
        $e->someField = 'foo.png';
        $this->assertEquals(
            'http://assets/someentity/someField/foo.png',
            $this->fm->getFileUrl($e, 'someField', new \Symfony\Component\HttpFoundation\File\File('/tmp/foo.png', false))
        );
    }

    function testGetFileUrlReturnsNullIfEmpty()
    {
        $e = new SomeEntity;
        $e->someField = '';
        $this->assertEquals(
            null,
            $this->fm->getFileUrl($e, 'someField')
        );
    }

    function testGetFilePath()
    {
        $e = new SomeEntity;
        $e->someField = 'foo.png';
        $this->assertEquals(
            '/media/someentity/someField/foo.png',
            $this->fm->getFilePath($e, 'someField')
        );
    }

    function testGetFilePathWithSpecifiedFile()
    {
        $e = new SomeEntity;
        $e->someField = 'foo.png';
        $this->assertEquals(
            '/media/someentity/someField/bar.png',
            $this->fm->getFilePath($e, 'someField', 'bar.png')
        );
    }

    function testGetFilePathWithFileObject()
    {
        $e = new SomeEntity;
        $e->someField = 'foo.png';
        $this->assertEquals(
            '/media/someentity/someField/foo.png',
            $this->fm->getFilePath($e, 'someField', new \Symfony\Component\HttpFoundation\File\File('/tmp/foo.png', false))
        );
    }

    function testGetFilePathReturnsNullIfEmpty()
    {
        $e = new SomeEntity;
        $e->someField = '';
        $this->assertEquals(
            null,
            $this->fm->getFilePath($e, 'someField')
        );
    }
}
