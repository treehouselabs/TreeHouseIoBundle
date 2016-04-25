<?php

namespace TreeHouse\IoBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('tree_house_io')->children();

        $rootNode
            ->scalarNode('data_dir')
            ->defaultValue('%kernel.root_dir%/var/data')
        ;

        $rootNode
            ->scalarNode('origin_manager_id')
            ->isRequired()
        ;

        $rootNode
            ->scalarNode('source_manager_id')
            ->isRequired()
        ;

        $rootNode
            ->scalarNode('source_processor_id')
            ->defaultValue('tree_house.io.source.processor.delegating')
        ;

        $rootNode
            ->scalarNode('source_cleaner_id')
            ->defaultValue('tree_house.io.source.cleaner.delegating')
        ;

        $rootNode
            ->arrayNode('import')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('dir')
                        ->defaultValue('%tree_house.io.data_dir%/import')
                    ->end()

                    ->arrayNode('import_part')
                        ->children()
                            ->scalarNode('time_to_run')
                                ->defaultValue(1200)
                            ->end()
                        ->end()
                    ->end()

                    ->arrayNode('item_logger')
                        ->beforeNormalization()
                            ->ifString()
                            ->then(function ($type) {
                                return ['type' => $type];
                            })
                        ->end()
                        ->children()
                            ->enumNode('type')
                                ->values(['array', 'redis', 'predis'])
                            ->end()
                            ->scalarNode('client')
                            ->end()
                            ->scalarNode('service')
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('reader')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->arrayNode('multipart')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('default_part_size')
                                        ->defaultValue(1000)
        ;

        $rootNode
            ->arrayNode('export')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('dir')
                        ->defaultValue('%tree_house.io.data_dir%/export')
                    ->end()
                    ->scalarNode('cache_dir')
                        ->info('Will store cache files for individual items')
                        ->defaultValue('%tree_house.io.export.dir%/cache')
                    ->end()
                    ->scalarNode('output_dir')
                        ->info('Will store final export file for all exported feeds')
                        ->defaultValue('%tree_house.io.export.dir%/generated')
                    ->end()
                ->end()
            ->end()
        ;

        $rootNode
            ->arrayNode('bridges')
            ->prototype('scalar')
            ->validate()
            ->ifTrue(function ($bridge) {
                return !in_array($bridge, ['WorkerBundle']);
            })
            ->thenInvalid('Invalid bridge %s. Valid bridge values are: "WorkerBundle"')
        ;

        return $treeBuilder;
    }
}
