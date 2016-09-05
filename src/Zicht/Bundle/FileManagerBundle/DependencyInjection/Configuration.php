<?php
/**
 * @author Rik van der Kemp <rik@zicht.nl>
 * @copyright Zicht Online <http://www.zicht.nl>
 */

namespace Zicht\Bundle\FileManagerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Zicht\Bundle\FileManagerBundle\Mapping\DefaultNamingStrategy;

/**
 * Class Configuration
 *
 * @package Zicht\Bundle\FileManagerBundle\DependencyInjection
 */
class Configuration implements ConfigurationInterface
{

    /**
     * Generates the configuration tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('zicht_file_manager');
        
        $rootNode->children()
            // Default behaviour is to lower case file names
            ->scalarNode('case_preservation')->defaultFalse();

        $rootNode->children()->scalarNode('naming_strategy')->defaultValue(DefaultNamingStrategy::class);

        return $treeBuilder;
    }
}
