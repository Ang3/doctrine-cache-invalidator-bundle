<?php

namespace Ang3\Bundle\DoctrineCacheInvalidatorBundle\Mapping;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration du fichier de mapping du cache.
 *
 * @author Joanis ROUANET
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}.
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root('mapping');

        $rootNode
            ->useAttributeAsKey('name')
            ->arrayPrototype()
                ->arrayPrototype()
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('parameters')
                            ->useAttributeAsKey('name')
                            ->scalarPrototype()->end()
                        ->end()
                        ->scalarNode('validation')->defaultNull()->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
