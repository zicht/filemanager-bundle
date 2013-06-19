<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace ZichtTest\Bundle\FileManagerBundle\Form\Transformer;


class FileTransformerTest extends \PHPUnit_Framework_TestCase
{
    function testTransform()
    {
        $v = null;
        $transformer = new \Zicht\Bundle\FileManagerBundle\Form\Transformer\FileTransformer(
            function($param) use(&$v) {
                $v = $param;
            }
        );
        $expect = rand(1, 20);
        $transformer->transform($expect);
        $this->assertEquals($expect, $v);
    }

    function testTransformReturnsNullIfFileNotFound()
    {
        $transformer = new \Zicht\Bundle\FileManagerBundle\Form\Transformer\FileTransformer(
            function($param) use(&$v) {
                throw new \Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException('soz');
            }
        );

        $this->assertNull($transformer->transform('foo'));
    }



    function testReverseTransform()
    {
        $transformer = new \Zicht\Bundle\FileManagerBundle\Form\Transformer\FileTransformer(function() {});
        $value = rand(1, 100);
        $this->assertEquals($value, $transformer->reverseTransform($value));
    }
}
