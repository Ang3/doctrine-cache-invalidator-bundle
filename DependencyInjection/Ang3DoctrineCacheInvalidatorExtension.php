<?php

namespace Ang3\Bundle\DoctrineCacheInvalidatorBundle\DependencyInjection;

use InvalidArgumentException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * @author Joanis ROUANET
 */
class Ang3DoctrineCacheInvalidatorExtension extends Extension implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Envoi des paramètres du cache dans le container
        $container->setParameter('ang3_doctrine_cache_invalidator.mapping', $config['mapping']);

        // Envoi des paramètres du cache dans le container
        $container->setParameter('ang3_doctrine_cache_invalidator.logger', $config['logger']);

        // Définition d'un chargeur de fichier YAML
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        // Chargement des services
        $loader->load('services.yml');
    }

    /**
     * {@inheritdoc}.
     */
    public function process(ContainerBuilder $container)
    {
        // Si on a un logger
        if ($logger = $container->getParameter('ang3_doctrine_cache_invalidator.logger')) {
            // Si le service n'existe pas
            if (!$container->hasDefinition($logger)) {
                throw new InvalidArgumentException(sprintf('Unable to find service "%s"', $logger));
            }

            // Normalisation du nom du service logger
            $logger = '@' == substr($logger, 0, 1) ? substr($logger, 1) : $logger;

            // Enregistrement d'un alias sur le logger
            $container->setAlias('ang3_doctrine_cache_invalidator.logger', $logger);
        }
    }
}
