<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */

namespace Zicht\Bundle\FileManagerBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension as DIExtension;

/**
 * Loads the DI extension for the ZichtFileManagerBundle.
 */
class ZichtFileManagerExtension extends DIExtension
{
    /**
     * @{inheritDoc}
     */
    public function load(array $config, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $config);

        $loader = new XmlFileLoader($container, new FileLocator(array(__DIR__.'/../Resources/config/')));
        $loader->load('services.xml');

        $formResources = $container->getParameter('twig.form.resources');
        $formResources[] = 'ZichtFileManagerBundle::form_theme.html.twig';
        $container->setParameter('twig.form.resources', $formResources);

        $container->setParameter('zicht_filemanager.naming_strategy.case_preservation', $config['case_preservation']);
        $container->setParameter('zicht_filemanager.naming_strategy.class', $config['naming_strategy']);
    }
}
