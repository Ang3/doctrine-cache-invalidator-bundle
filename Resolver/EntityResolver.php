<?php

namespace Ang3\Bundle\DoctrineCacheInvalidatorBundle\Resolver;

use Exception;
use Ang3\Bundle\DoctrineCacheInvalidatorBundle\Exception\MappingException;
use Doctrine\Common\Util\ClassUtils;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * @author Joanis ROUANET
 */
class EntityResolver implements ResolverInterface
{
    /**
     * Symfony expression language component.
     *
     * @var ExpressionLanguage
     */
    protected $expressionLanguage;

    /**
     * Invalidator mapping.
     *
     * @var array
     */
    protected $mapping = [];

    /**
     * Constructor of the resolver.
     *
     * @param array $mapping
     */
    public function __construct(array $mapping = [])
    {
        $this->mapping = $mapping;
        $this->expressionLanguage = new ExpressionLanguage();
    }

    /**
     * {@inheritdoc}.
     */
    public function resolve($entity, $eventType, array $changeSet = [])
    {
        // Initialisation des clés
        $cacheKeys = [];

        // Si pas de mapping
        if (!$this->mapping) {
            // Retour vide
            return $cacheKeys;
        }

        // Définition du FQCN de l'entité
        $entityClass = ClassUtils::getClass($entity);

        // Récupération des interfaces implémentées
        $entityInterfaces = class_implements($entityClass);

        // Pour chaque clé mappée
        foreach ($this->mapping as $key => $classes) {
            // Extraction des paramètres de l'id de cache
            $keyParameters = $this->extractKeyParameters($key);

            // Pour chaque classe concernée par cet index
            foreach ($classes as $class => $options) {
                // Si la classe n'existe pas
                if (!class_exists($class)) {
                    throw new MappingException($key, $class, sprintf('The class "%s" was not found.', $class), 0);
                }

                // Si l'entité ne correspond pas à la classe ou interface
                if ($entityClass !== $class && !in_array($class, $entityInterfaces)) {
                    // Classe suivante
                    continue;
                }

                // Initialisation de la clé finale
                $finalKey = $key;

                // Si on a des paramètre dans l'index
                if (count($keyParameters) > 0) {
                    // Si pas de paramètre dans la config
                    if (!isset($options['parameters'])) {
                        throw new MappingException($key, $class, 'Missing parameters', 1);
                    }

                    // Pour chaque paramètre de l'index
                    foreach ($keyParameters as $name) {
                        // Si le paramètre n'est pas dans la configuration
                        if (!array_key_exists($name, $options['parameters'])) {
                            throw new MappingException($key, $class, sprintf('Missing parameter "%s"', $name), 2);
                        }

                        try {
                            // Récupération de la valeur du paramètre
                            $paramValue = (string) $this->expressionLanguage->evaluate($options['parameters'][$name], [
                                'this' => $entity,
                                'eventType' => $eventType,
                                'changeSet' => $changeSet,
                            ]);
                        } catch (Exception $e) {
                            throw new MappingException($key, $class, null, 3, $e);
                        }

                        // On remplace la valeur du paramètre dans l'index
                        $finalKey = str_replace("\$$name", $paramValue, $finalKey);
                    }
                }

                // Si on a des paramètres de validation
                if (isset($options['validation'])) {
                    // Initialisation de la validation
                    $validation = false;

                    try {
                        // Tentative de récupération du résultat de l'expression
                        $validation = $this->expressionLanguage->evaluate($options['validation'], [
                            'this' => $entity,
                            'eventType' => $eventType,
                            'changeSet' => $changeSet,
                        ]);
                    } catch (Exception $e) {
                        throw new MappingException($key, $class, null, 4, $e);
                    }

                    // Si la validation n'est pas passée
                    if (!$validation) {
                        // Classe suivante
                        continue;
                    }
                }

                // Enregistrement de la clé
                $cacheKeys[] = $finalKey;
            }
        }

        // Retour du dédoublonnage éventuel
        return array_unique($cacheKeys);
    }

    /**
     * Extract parameters from a cache key.
     *
     * @param string $key
     *
     * @return array
     */
    protected function extractKeyParameters($key)
    {
        // Recherche des index
        if (preg_match_all('#\$(\$?\w+)#', (string) $key, $matches)) {
            // Enregistrement des parametres eventuels
            return $matches[1];
        }

        // Retour vide par défaut
        return [];
    }
}
