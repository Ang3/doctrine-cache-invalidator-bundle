<?php

namespace Ang3\Bundle\DoctrineCacheInvalidatorBundle\Resolver;

use Exception;
use Ang3\Bundle\DoctrineCacheInvalidatorBundle\Exception\CacheInvalidationException;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * @author Joanis ROUANET
 */
class CacheIdResolver implements CacheIdResolverInterface
{
    /**
     * Doctrine entity manager.
     *
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * Symfony expression language component.
     *
     * @var ExpressionLanguage
     */
    protected $expressionLanguage;

    /**
     * Constructor of the resolver.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->expressionLanguage = new ExpressionLanguage();
    }

    /**
     * {@inheritdoc}.
     */
    public function resolve($entity, $eventType, array $changeSet = [])
    {
        // Récupération de la classe de l'entité
        $entityClass = ClassUtils::getClass($entity);

        // Récupération des métadonnées de l'entité
        $classMetadata = $this->entityManager->getClassMetadata($entityClass);

        // Initialisation des ID de cache
        $cacheIds = [];

        // Pour chaque annotation d'invalidation du cache de la classe
        foreach ($this->annotationReader->getClassAnnotations($classMetadata->getReflectionClass()) as $annotation) {
            // Si c'est une annotation d'invalidation du cache
            if ($annotation instanceof CacheInvalidation) {
                // Récupération de l'ID de cache
                $cacheId = $annotation->id;

                // Récupération des paramètres éventuels de l'ID
                $cacheIdParameters = $this->extractCacheIdParameters($cacheId);

                // Si on a des paramètre dans l'index
                if (count($cacheIdParameters) > 0) {
                    // Si pas de paramètre dans la config
                    if (!$annotation->parameters) {
                        throw new CacheInvalidationException(sprintf('Missing parameters expressions for the cache id "%s" in class "%s".', $cacheId, $entityClass));
                    }

                    // Pour chaque paramètre de l'index
                    foreach ($cacheIdParameters as $name) {
                        // Si le paramètre n'est pas dans la configuration
                        if (!array_key_exists($name, $annotation->parameters)) {
                            throw new CacheInvalidationException(sprintf('Missing expression for parameter "%s" for the cache id "%s" in class "%s".', $name, $cacheId, $entityClass));
                        }

                        try {
                            // Récupération de la valeur du paramètre
                            $paramValue = (string) $this->expressionLanguage
                                ->evaluate(
                                    $annotation->parameters[$name],
                                    [
                                    'this' => $entity,
                                    'eventType' => $eventType,
                                    'changeSet' => $changeSet,
                                ]
                            );
                        } catch (Exception $e) {
                            throw new CacheInvalidationException('Unable to resolve parameter "%s" for the cache id "%s" - %s in class "%s".', $name, $cacheId, $e->getMessage(), $entityClass);
                        }

                        // On remplace la valeur du paramètre dans l'index
                        $cacheId = str_replace("\$$name", $paramValue, $cacheId);
                    }

                    // Si on a une expression de validation
                    if ($annotation->validation) {
                        // Initialisation de la validation
                        $validation = false;

                        try {
                            // Tentative de récupération du résultat de l'expression de validation
                            $validation = (bool) $this->expressionLanguage
                                ->evaluate(
                                    $annotation->validation,
                                    [
                                    'this' => $entity,
                                    'eventType' => $eventType,
                                    'changeSet' => $changeSet,
                                ]
                            );
                        } catch (Exception $e) {
                            throw new CacheInvalidationException('Unable to validate the cache id "%s" in class "%s" - %s', $cacheId, $entityClass, $e->getMessage());
                        }

                        // Si la validation n'est pas passée
                        if (!$validation) {
                            // Classe suivante
                            continue;
                        }
                    }

                    // Enregistrement de la clé
                    $cacheIds[] = $cacheId;
                }
            }
        }

        // Retour du dédoublonnage éventuel
        return array_unique($cacheIds);
    }

    /**
     * Extract parameters from a cache id.
     *
     * @param string $key
     *
     * @return array
     */
    protected function extractCacheIdParameters($key)
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
