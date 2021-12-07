<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace ZichtTest\Bundle\FileManagerBundle\Form\Transformer;

use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Zicht\Bundle\FileManagerBundle\FileManager\FileManager;
use Zicht\Bundle\FileManagerBundle\Helper\PurgatoryHelper;

class FileTypeTestEntity
{
    public $foo;
}

class FileTypeTest extends TestCase
{
    public function setUp(): void
    {
        $this->fm = $this->getMockBuilder('Zicht\Bundle\FileManagerBundle\FileManager\FileManager')
            ->setMethods(['getFilePath'])
            ->disableOriginalConstructor()
            ->getMock();
    }


    function testType()
    {
        $type = new \Zicht\Bundle\FileManagerBundle\Form\FileType($this->fm, $this->createMock(\Symfony\Bundle\FrameworkBundle\Translation\Translator::class));

        $opt = new OptionsResolver();
        $type->configureOptions($opt);
        $opt = $opt->resolve([]);

        $this->assertEquals($opt['show_current_file'], true);

        $this->assertEquals('zicht_file', $type->getBlockPrefix());
        $this->assertEquals('Symfony\Component\Form\Extension\Core\Type\FormType', $type->getParent());
    }


    function testBuildForm()
    {
        $name = 'foo';
        $entity = new FileTypeTestEntity();

        $type = new \Zicht\Bundle\FileManagerBundle\Form\FileType($this->fm, $this->createMock(\Symfony\Bundle\FrameworkBundle\Translation\Translator::class));

        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->setMethods(['getName', 'getParent', 'getAttribute', 'setAttribute', 'addViewTransformer', 'addEventListener', 'addEventSubscriber'])
            ->disableOriginalConstructor()
            ->getMock();
        $parentBuilder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->setMethods(['getDataClass'])
            ->disableOriginalConstructor()
            ->getMock();

        $builder->method('getName')->will($this->returnValue($name));
        $attributes = ['sonata_admin' => ['field_description' => false], 'entity' => get_class($entity), 'property' => 'foo'];
        $builder->expects($this->any())->method('setAttribute')->will(
            $this->returnCallback(function ($k, $v) use (&$attributes) {
                $attributes[$k] = $v;
            })
        );

        $builder->expects($this->any())->method('getAttribute')->will(
            $this->returnCallback(function ($k) use (&$attributes) {
                return $attributes[$k];
            })
        );

        $self = $this;
        $transformer = null;
        $builder->expects($this->once())->method('addViewTransformer')->will(
            $this->returnCallback(function ($t) use ($self, &$transformer) {
                $self->assertInstanceOf('Zicht\Bundle\FileManagerBundle\Form\Transformer\FileTransformer', $t);
                $transformer = $t;
            })
        );

        $opt = new \Symfony\Component\OptionsResolver\OptionsResolver();
        $type->configureOptions($opt);
        $opt = $opt->resolve([]);

        $type->buildForm($builder, $opt);

        $this->assertEquals(get_class($entity), $attributes['entity']);
        $this->assertEquals($name, $attributes['property']);
    }

    private function setupForm(array $methods, FileManager $fm)
    {
        $type = new \Zicht\Bundle\FileManagerBundle\Form\FileType($fm, $this->createMock(\Symfony\Bundle\FrameworkBundle\Translation\Translator::class));
        $view = new \Symfony\Component\Form\FormView();
        $form = $this->getMockBuilder('Symfony\Component\Form\FormInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $config = $this->getMockBuilder('Symfony\Component\Form\FormConfigInterface')->getMock();
        $attr = [
            'entity' => 43,
            'property' => rand(1, 100),
            'sonata_admin' => ['field_description' => false],
        ];
        $opt = [
            'show_current_file' => rand(1, 100),
            'show_remove' => false,
            'allow_url' => false,
        ];
        $config->expects($this->any())->method('getAttribute')->will(
            $this->returnCallback(function ($n) use ($attr) {
                return $attr[$n];
            })
        );
        $config->expects($this->any())->method('getOption')->will(
            $this->returnCallback(function ($n) use ($opt) {
                return $opt[$n];
            })
        );

        $form->expects($this->any())->method('getConfig')->will($this->returnValue($config));

        return [$type, $view, $form, $config, $attr, $opt];
    }

    function testFinishViewWithFormDataIsNull()
    {
        list($type, $view, $form, $config, $attr, $opt) = $this->setupForm(['getData'], $this->fm);

        $form->expects($this->any())->method('getData')->will($this->returnValue(null));
        $type->finishView($view, $form, []);

        $this->assertEquals($opt['show_current_file'], $view->vars['show_current_file']);
        // TODO fix retrieving entity...this means fixing/enabling/mocking the addEventListener-call in FileType::buildForm
        //$this->assertEquals($attr['entity'], $view->vars['entity']);
        $this->assertEquals($attr['property'], $view->vars['property']);

        $this->assertArrayNotHasKey('file_url', $view->vars);

        $this->assertArrayNotHasKey('purgatory_field_postfix', $view->vars);
        $this->assertArrayNotHasKey('purgatory_file_filename', $view->vars);
        $this->assertArrayNotHasKey('purgatory_file_hash', $view->vars);
    }

    function testFinishViewWithAlreadyUploadedFile()
    {
        $fm = $this->getMockBuilder('Zicht\Bundle\FileManagerBundle\FileManager\FileManager')
            ->setMethods(['getFilePath', 'getFileUrl'])
            ->disableOriginalConstructor()
            ->getMock();

        list($type, $view, $form, $config, $attr, $opt) = $this->setupForm(['getData'], $fm);

        $expectedFileName = 'foo.png';
        $expectedPath = '/entity/path/' . $expectedFileName;

        $view->vars['value'] = $expectedFileName;

        //$fm->expects($this->once())->method('getFileUrl')->with($attr['entity'], $attr['property'], $expectedFileName)->will(
        //    $this->returnValue($expectedPath)
        //);

        $form->expects($this->any())->method('getData')->will($this->returnValue(null));

        $type->finishView($view, $form, []);

        $this->assertEquals($opt['show_current_file'], $view->vars['show_current_file']);
        // TODO fix retrieving entity...this means fixing/enabling/mocking the addEventListener-call in FileType::buildForm
        // $this->assertEquals($attr['entity'], $view->vars['entity']);
        //$this->assertEquals($view->vars['file_url'], $expectedPath);

        $this->assertEquals($attr['property'], $view->vars['property']);

        $this->assertArrayNotHasKey('purgatory_field_postfix', $view->vars);
        $this->assertArrayNotHasKey('purgatory_file_filename', $view->vars);
        $this->assertArrayNotHasKey('purgatory_file_hash', $view->vars);
    }

    function testFinishViewWithFormUploadedFile()
    {
        $expectedFileName = 'foo.png';
        $expectedPath = '/entity/path/' . $expectedFileName;
        $httpRoot = 'www.example.com';

        $fm = $this->getMockBuilder('Zicht\Bundle\FileManagerBundle\FileManager\FileManager')
            ->setMethods(['getFilePath', 'getFileUrl'])
            ->disableOriginalConstructor()
            ->getMock();

        $fm->setHttpRoot($httpRoot);

        list($type, $view, $form, $config, $attr, $opt) = $this->setupForm(['getData'], $fm);

        $formData = $this->getMockBuilder('\Symfony\Component\HttpFoundation\File\UploadedFile')
            ->setMethods(['getBaseName'])
            ->disableOriginalConstructor()
            ->getMock();
        $formData->expects($this->any())->method('getBaseName')->will($this->returnValue('foo.png'));
        $form->expects($this->any())->method('getData')->will($this->returnValue($formData));

        // TODO fix retrieving entity...this means fixing/enabling/mocking the addEventListener-call in FileType::buildForm
        //$fm->expects($this->any())->method('getFileUrl')->with($attr['entity'], $attr['property'], $formData)->will(
        //    $this->returnValue($expectedPath)
        //);
        // $this->assertEquals($attr['entity'], $view->vars['entity']);
        // $this->assertEquals($view->vars['file_url'], $expectedPath);
        $type->finishView($view, $form, []);
        $this->assertEquals($opt['show_current_file'], $view->vars['show_current_file']);
        $this->assertEquals($attr['property'], $view->vars['property']);

        //$this->assertEquals($view->vars['purgatory_field_postfix'], PurgatoryHelper::makePostFix($attr['entity'], $attr['property']));
        //$this->assertEquals($view->vars['purgatory_file_filename'], $expectedFileName);
        //$this->assertEquals($view->vars['purgatory_file_hash'], PurgatoryHelper::makeHash($attr['entity'], $attr['property'], $expectedFileName));

        $this->assertArrayNotHasKey('purgatory_field_postfix', $view->vars);
        $this->assertArrayNotHasKey('purgatory_file_filename', $view->vars);
        $this->assertArrayNotHasKey('purgatory_file_hash', $view->vars);

        $this->assertEquals($fm->getHttpRoot(), $httpRoot);
    }
}
