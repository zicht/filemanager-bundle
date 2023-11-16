<?php declare(strict_types=1);

namespace ZichtTest\Bundle\FileManagerBundle\Metadata\Driver;

use PHPUnit\Framework\TestCase;
use Zicht\Bundle\FileManagerBundle\Annotation\File;

class MyClass2
{
    #[File]
    public $file;
    public $noFile;
}

class AttributeDriverTest extends TestCase
{
    public function setUp(): void
    {
        $this->driver = new \Zicht\Bundle\FileManagerBundle\Metadata\Driver\AttributeDriver();
    }

    function testLoadMetadataForClass()
    {
        if (\PHP_MAJOR_VERSION < 8) {
            $this->markTestSkipped('PHP < 8');
            return;
        }

        $refl = new \ReflectionClass(MyClass2::class);
        $metadata = $this->driver->loadMetadataForClass($refl);

        $this->assertArrayHasKey('file', $metadata->propertyMetadata);
        $this->assertArrayNotHasKey('noFile', $metadata->propertyMetadata);
        $filePropertyMetaData = $metadata->propertyMetadata['file'];
        $this->assertInstanceOf(
            \Zicht\Bundle\FileManagerBundle\Metadata\PropertyMetadata::class,
            $filePropertyMetaData
        );
        $this->assertSame($filePropertyMetaData->class, MyClass2::class);
        $this->assertSame($filePropertyMetaData->name, 'file');
        $this->assertNull($filePropertyMetaData->fileManager);
    }
}
