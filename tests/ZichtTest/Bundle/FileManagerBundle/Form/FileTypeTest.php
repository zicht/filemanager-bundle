<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace ZichtTest\Bundle\FileManagerBundle\Form\Transformer;

use Zicht\Bundle\FileManagerBundle\FileManager\FileManager;
use Zicht\Bundle\FileManagerBundle\Helper\PurgatoryHelper;

class FileTypeTestEntity
{
    public $foo;
}

class FileTypeTest extends \PHPUnit_Framework_TestCase
{
    function setUp()
    {
        $this->fm = $this->getMockBuilder('Zicht\Bundle\FileManagerBundle\FileManager\FileManager')
            ->setMethods(array('getFilePath'))
            ->disableOriginalConstructor()
            ->getMock();
    }


    function testType()
    {
        $type = new \Zicht\Bundle\FileManagerBundle\Form\FileType($this->fm);

        $opt = new \Symfony\Component\OptionsResolver\OptionsResolver();
        $type->setDefaultOptions($opt);
        $opt = $opt->resolve(array());

        $this->assertEquals($opt['data_class'], 'Symfony\Component\HttpFoundation\File\File');
        $this->assertEquals($opt['show_current_file'], true);

        $this->assertEquals('zicht_file', $type->getName());
        $this->assertEquals('field', $type->getParent());
    }


    function testBuildForm()
    {
        $name = 'foo';
        $entity = new FileTypeTestEntity();

        $type = new \Zicht\Bundle\FileManagerBundle\Form\FileType($this->fm);

        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->setMethods(array('getName', 'getParent', 'getAttribute', 'setAttribute', 'addViewTransformer', 'addEventSubscriber'))
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $parentBuilder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->setMethods(array('getDataClass'))
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $parentBuilder->expects($this->once())->method('getDataClass')->will($this->returnValue(get_class($entity)));

        $builder->expects($this->once())->method('getName')->will($this->returnValue($name));
        $builder->expects($this->once())->method('getParent')->will($this->returnValue(
            $parentBuilder
        ));
        $attributes = array();
        $builder->expects($this->any())->method('setAttribute')->will($this->returnCallback(function($k, $v) use(&$attributes) {
            $attributes[$k] = $v;
        }));

        $builder->expects($this->any())->method('getAttribute')->will($this->returnCallback(function($k) use(&$attributes) {
            return $attributes[$k];
        }));

        $self = $this;
        $transformer = null;
        $builder->expects($this->once())->method('addViewTransformer')->will($this->returnCallback(function($t) use($self, &$transformer) {
            $self->assertInstanceOf('Zicht\Bundle\FileManagerBundle\Form\Transformer\FileTransformer', $t);
            $transformer = $t;
        }));

        $opt = new \Symfony\Component\OptionsResolver\OptionsResolver();
        $type->setDefaultOptions($opt);
        $opt = $opt->resolve(array());

        $type->buildForm($builder, $opt);

        $this->assertEquals(get_class($entity), $attributes['entity']);
        $this->assertEquals($name, $attributes['property']);

        touch('/tmp/baz');
        $this->fm->expects($this->once())->method('getFilePath')->with(get_class($entity), $name, 'bar.png')->will(
            $this->returnValue('/tmp/baz')
        );
        $transformed = $transformer->transform('bar.png');
        unlink('/tmp/baz');
        $this->assertEquals('/tmp/baz', $transformed);
    }

    private function setupForm(array $methods, FileManager $fm)
    {
        $type = new \Zicht\Bundle\FileManagerBundle\Form\FileType($fm);
        $view = new \Symfony\Component\Form\FormView();
        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->setMethods(array_merge(array('getConfig'), $methods))
            ->getMock();
        $config = $this->getMock('\stdClass', array('getAttribute', 'getOption'));
        $attr = array(
            'entity' => rand(1, 100),
            'property' => rand(1, 100)
        );
        $opt = array(
            'show_current_file' => rand(1, 100)
        );
        $config->expects($this->any())->method('getAttribute')->will($this->returnCallback(function($n) use($attr) {
                    return $attr[$n];
                }));
        $config->expects($this->any())->method('getOption')->will($this->returnCallback(function($n) use($opt) {
                    return $opt[$n];
                }));

        $form->expects($this->any())->method('getConfig')->will($this->returnValue($config));

        return array($type, $view, $form, $config, $attr, $opt);
    }

    function testFinishViewWithFormDataIsNull()
    {
        list($type, $view, $form, $config, $attr, $opt) = $this->setupForm(array('getData'), $this->fm);

        $form->expects($this->any())->method('getData')->will($this->returnValue(null));
        $type->finishView($view, $form, array());

        $this->assertEquals($opt['show_current_file'], $view->vars['show_current_file']);
        $this->assertEquals($attr['entity'], $view->vars['entity']);
        $this->assertEquals($attr['property'], $view->vars['property']);

        $this->assertArrayNotHasKey('file_url', $view->vars);

        $this->assertArrayNotHasKey('purgatory_field_postfix', $view->vars);
        $this->assertArrayNotHasKey('purgatory_file_filename', $view->vars);
        $this->assertArrayNotHasKey('purgatory_file_hash', $view->vars);
    }

    function testFinishViewWithAlreadyUploadedFile()
    {
        $fm = $this->getMockBuilder('Zicht\Bundle\FileManagerBundle\FileManager\FileManager')
            ->setMethods(array('getFilePath', 'getFileUrl'))
            ->disableOriginalConstructor()
            ->getMock();

        list($type, $view, $form, $config, $attr, $opt) = $this->setupForm(array('getData'), $fm);

        $expectedFileName = 'foo.png';
        $expectedPath     = '/entity/path/' . $expectedFileName;

        $view->vars['value'] = $expectedFileName;

        $fm->expects($this->once())->method('getFileUrl')->with($attr['entity'], $attr['property'], $expectedFileName)->will(
            $this->returnValue($expectedPath)
        );

        $form->expects($this->any())->method('getData')->will($this->returnValue(null));

        $type->finishView($view, $form, array());

        $this->assertEquals($opt['show_current_file'], $view->vars['show_current_file']);
        $this->assertEquals($attr['entity'], $view->vars['entity']);
        $this->assertEquals($attr['property'], $view->vars['property']);

        $this->assertEquals($view->vars['file_url'], $expectedPath);

        $this->assertArrayNotHasKey('purgatory_field_postfix', $view->vars);
        $this->assertArrayNotHasKey('purgatory_file_filename', $view->vars);
        $this->assertArrayNotHasKey('purgatory_file_hash', $view->vars);
    }

    function testFinishViewWithFormUploadedFile()
    {
        $expectedFileName = 'foo.png';
        $expectedPath     = '/entity/path/' . $expectedFileName;
        $httpRoot = 'www.example.com';

        $fm = $this->getMockBuilder('Zicht\Bundle\FileManagerBundle\FileManager\FileManager')
            ->setMethods(array('getFilePath', 'getFileUrl'))
            ->disableOriginalConstructor()
            ->getMock();

        $fm->setHttpRoot($httpRoot);

        list($type, $view, $form, $config, $attr, $opt) = $this->setupForm(array('getData'), $fm);

        $formData = $this->getMockBuilder('\Symfony\Component\HttpFoundation\File\UploadedFile')
            ->setMethods(array('getBaseName'))
            ->disableOriginalConstructor()
            ->getMock();
        $formData->expects($this->any())->method('getBaseName')->will($this->returnValue('foo.png'));
        $form->expects($this->any())->method('getData')->will($this->returnValue($formData));

        $fm->expects($this->any())->method('getFileUrl')->with($attr['entity'], $attr['property'], $formData)->will(
            $this->returnValue($expectedPath)
        );

        $type->finishView($view, $form, array());

        $this->assertEquals($opt['show_current_file'], $view->vars['show_current_file']);
        $this->assertEquals($attr['entity'], $view->vars['entity']);
        $this->assertEquals($attr['property'], $view->vars['property']);

        $this->assertEquals($view->vars['file_url'], $expectedPath);

        $this->assertEquals($view->vars['purgatory_field_postfix'], PurgatoryHelper::makePostFix($attr['entity'], $attr['property']));
        $this->assertEquals($view->vars['purgatory_file_filename'], $expectedFileName);
        $this->assertEquals($view->vars['purgatory_file_hash'], PurgatoryHelper::makeHash($attr['entity'], $attr['property'], $expectedFileName));

        $this->assertEquals($fm->getHttpRoot(), $httpRoot);
    }
}