<?php
/**
 * @copyright 2012 Gerard van Helden <http://melp.nl>
 */

namespace ZichtTest\Bundle\AdminBundle\DependencyInjection\Compiler;

use Zicht\Bundle\FileManagerBundle\DependencyInjection\ZichtFileManagerExtension;

class ZichtFileManagerExtensionTest extends \PHPUnit_Framework_TestCase
{
    function testLoadWillLoadXmlFile()
    {
        $e = new ZichtFileManagerExtension();
        $builder = new \Symfony\Component\DependencyInjection\ContainerBuilder();
        $builder->setParameter('twig.form.resources', []);
        $e->load([], $builder);

        $this->assertTrue($builder->hasDefinition('zicht_filemanager.filemanager'));
    }
}
