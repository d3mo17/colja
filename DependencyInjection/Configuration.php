<?php

namespace DMo\Colja\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('d_mo_colja');

        if (method_exists($treeBuilder, 'getRootNode')) {
            $rootNode = $treeBuilder->getRootNode();
        } else {
            // BC for symfony/config < 4.2
            $rootNode = $treeBuilder->root('d_mo_colja');
        }
        $rootNode
            ->children()
                ->scalarNode('schema')
                    ->defaultValue('config/graphql/base.schema')
                    ->cannotBeEmpty()
                ->end()
                ->arrayNode('query')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('class')->isRequired()->end()
                            ->scalarNode('method')->isRequired()->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('mutation')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('class')->isRequired()->end()
                            ->scalarNode('method')->isRequired()->end()
                        ->end()
                    ->end()
                ->end()
                ->booleanNode('option_request_user_restricted')
                    ->defaultFalse()
                ->end()
                ->arrayNode('client')
                    ->children()
                        ->scalarNode('user_agent')->end()
                        ->integerNode('connect_timeout')->defaultValue(45)->end()
                        ->integerNode('read_timeout')->defaultValue(45)->end()
                        ->integerNode('timeout')->defaultValue(45)->end()
                    ->end()
                ->end() // client
                ->arrayNode('extensions')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('schema')->isRequired()->cannotBeEmpty()->end()
                            ->arrayNode('query')
                                ->arrayPrototype()
                                    ->children()
                                        ->scalarNode('class')->isRequired()->end()
                                        ->scalarNode('method')->isRequired()->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('mutation')
                                ->arrayPrototype()
                                    ->children()
                                        ->scalarNode('class')->isRequired()->end()
                                        ->scalarNode('method')->isRequired()->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end() // extensions
            ->end()
        ;

        return $treeBuilder;
    }
}
