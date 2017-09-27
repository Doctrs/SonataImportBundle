<?php

namespace Doctrs\SonataImportBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('doctrs_sonata_import');

        $rootNode
            ->children()
                ->arrayNode('mappings')
                    ->defaultValue([])
                    ->prototype('array')
                        ->children()
                            ->scalarNode('name')->end()
                            ->scalarNode('class')->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('upload_dir')
                    ->defaultValue(null)
                ->end()
                ->scalarNode('class_loader')
                    ->defaultValue('Doctrs\SonataImportBundle\Loaders\CsvFileLoader')
                ->end()
                ->arrayNode('encode')
                    ->children()
                        ->scalarNode('default')->defaultValue('utf8')->end()
                        ->arrayNode('list')->defaultValue([])
                            ->prototype('scalar')
                            ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
