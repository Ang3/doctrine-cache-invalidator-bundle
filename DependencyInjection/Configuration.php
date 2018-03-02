<?php

namespace Ang3\Bundle\DoctrineCacheInvalidatorBundle\DependencyInjection;

use Ang3\Bundle\DoctrineCacheInvalidatorBundle\Resolver\CacheIdResolver;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration du bundle.
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

        $rootNode = $treeBuilder->root('ang3_doctrine_cache_invalidator');

        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('logger')->defaultNull()->end()
                ->scalarNode('resolver_class')->defaultValue(CacheIdResolver::class)->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
