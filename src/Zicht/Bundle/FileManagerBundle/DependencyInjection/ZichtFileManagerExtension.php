<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\FileManagerBundle\DependencyInjection;

use \Symfony\Component\HttpKernel\DependencyInjection\Extension as DIExtension;
use \Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use \Symfony\Component\Config\FileLocator;
use \Symfony\Component\DependencyInjection\ContainerBuilder;
use \Symfony\Component\DependencyInjection\Definition;

/**
 * Loads the DI extension for the ZichtFileManagerBundle
 */
class ZichtFileManagerExtension extends DIExtension
{
    /**
     * @{inheritDoc}
     */
    public function load(array $config, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(array(__DIR__.'/../Resources/config/')));
        $loader->load('services.xml');

        $formResources = $container->getParameter('twig.form.resources');
        $formResources[]= 'ZichtFileManagerBundle::form_theme.html.twig';
        $container->setParameter('twig.form.resources', $formResources);
    }
}