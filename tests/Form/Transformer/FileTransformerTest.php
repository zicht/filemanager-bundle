<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace ZichtTest\Bundle\FileManagerBundle\Form\Transformer;

use PHPUnit\Framework\TestCase;
use Zicht\Bundle\FileManagerBundle\Form\FileType;
use \Zicht\Bundle\FileManagerBundle\Form\Transformer\FileTransformer;
use \Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

class FileTransformerTest extends TestCase
{
    function testTransform()
    {
        $v = null;
        $transformer = new FileTransformer(
            function ($param) use (&$v) {
                $v = $param;
            },
            'foo'
        );
        $expect = rand(1, 20);
        $transformer->transform($expect);
        $this->assertEquals($expect, $v);
    }

    function testTransformReturnsNullIfFileNotFound()
    {
        $transformer = new FileTransformer(
            function () {
                throw new FileNotFoundException('soz');
            },
            'foo'
        );
        $this->expectException('Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException');
        $this->assertNull($transformer->transform('foo'));
    }


    function testReverseTransform()
    {
        $transformer = new \Zicht\Bundle\FileManagerBundle\Form\Transformer\FileTransformer(function () {
        }, 'foo');
        $value = 42;
        $this->assertEquals($value, $transformer->reverseTransform([FileType::UPLOAD_FIELDNAME => $value]));
    }
}
