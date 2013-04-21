<?php
/**
 * For licensing information, please see the LICENSE file accompanied with this file.
 *
 * @author Gerard van Helden <drm@melp.nl>
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
        $e->load(array(), $builder);

        $this->assertTrue($builder->hasDefinition('zicht_filemanager.filemanager'));
    }
}