<?php
/**
 * @copyright Zicht Online <http://zicht.nl>
 */
namespace Zicht\Bundle\FileManagerBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Initializes dependencies that are not hard dependencies.
 */
class SetOptionalDepsPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('event_dispatcher') || $container->hasAlias('event_dispatcher')) {
            $container->getDefinition('zicht_filemanager.filemanager')
                ->addMethodCall(
                    'setEventDispatcher',
                    [
                        new Reference('event_dispatcher')
                    ]
                );
        }
        if ($container->hasDefinition('liip_imagine.cache.manager') && $container->hasDefinition('liip_imagine.filter.configuration')) {
            $container->getDefinition('zicht_filemanager.filemanager')
                ->addMethodCall(
                    'setImagineConfig',
                    [
                        new Reference('liip_imagine.cache.manager'),
                        new Reference('liip_imagine.filter.configuration'),
                    ]
                );
        }
    }
}
