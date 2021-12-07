<?php
/**
 * @copyright 2012 Gerard van Helden <http://melp.nl>
 */

namespace ZichtTest\Bundle\FileManagerBundle\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use Zicht\Bundle\FileManagerBundle\DependencyInjection\ZichtFileManagerExtension;

class ZichtFileManagerExtensionTest extends TestCase
{
    function testLoadWillLoadXmlFile()
    {
        $e = new ZichtFileManagerExtension();
        $builder = new \Symfony\Component\DependencyInjection\ContainerBuilder();
        $builder->setParameter('twig.form.resources', []);
        $builder->setParameter('kernel.bundles', []);
        $e->load([], $builder);

        $this->assertTrue($builder->hasDefinition('zicht_filemanager.filemanager'));
    }
}
