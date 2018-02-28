<?php

namespace Ang3\Bundle\DoctrineCacheInvalidatorBundle\Factory;

use Exception;
use Ang3\Bundle\DoctrineCacheInvalidatorBundle\Mapping\Configuration;
use Ang3\Bundle\DoctrineCacheInvalidatorBundle\Resolver\EntityResolver;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;

/**
 * @author Joanis ROUANET
 */
class EntityResolverFactory
{
    /**
     * Creates a key resolver from mapping YAML file.
     *
     * @param string $path Chemin du fichier de mapping
     *
     * @return EntityResolver
     */
    public function createFromYaml($path)
    {
        // Si impossible de lire le fichier
        if (!is_file($path) || !is_readable($path)) {
            throw new Exception(sprintf('Unable to configure second level cache because the mapping file "%s" was not found.', $path));
        }

        // Récupération du contenu YAML
        $yamlContent = Yaml::parse(file_get_contents($path), Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE);

        // Récupération de la config YAML éventuelle du cache
        if (!$yamlContent) {
            return [];
        }

        // Récupération d'un processeur de configuration
        $processor = new Processor();

        // Retour du resolver avec le mapping en paramètre
        return new EntityResolver($processor->processConfiguration(new Configuration(), $yamlContent));
    }
}
