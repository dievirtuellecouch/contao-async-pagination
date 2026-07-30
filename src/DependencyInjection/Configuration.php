<?php

namespace DVC\AsyncPagination\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('async_pagination');

        $treeBuilder->getRootNode()
            ->children()
                ->integerNode('shared_max_age')
                    ->defaultValue(3600)
                ->end()
                ->arrayNode('target_frontend_model_types')
                    ->scalarPrototype()->end()
                    ->defaultValue([])
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
