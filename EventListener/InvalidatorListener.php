<?php

namespace Ang3\Bundle\DoctrineCacheInvalidatorBundle\EventListener;

use Ang3\Bundle\DoctrineCacheInvalidatorBundle\Exception\CacheInvalidationException;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Util\ClassUtils;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Psr\Log\LoggerInterface;

/**
 * Cache Invalidator listener.
 *
 * @author Joanis ROUANET
 */
class InvalidatorListener
{
    /**
     * Doctrine annotation reader.
     *
     * @var Reader
     */
    protected $annotationReader;

    /**
     * Symfony expression language component.
     *
     * @var ExpressionLanguage
     */
    protected $expressionLanguage;

    /**
     * Logger.
     *
     * @var LoggerInterface|null
     */
    protected $logger;

    /**
     * Constructor of the listener.
     *
     * @param Reader $annotationReader
     */
    public function __construct(Reader $annotationReader)
    {
        $this->annotationReader = $annotationReader;
        $this->expressionLanguage = new ExpressionLanguage();
    }

    /**
     * Sets logger.
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * A chaque ecriture en base.
     *
     * @param OnFlushEventArgs $eventArgs
     */
    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        // Récupération du manager des entités
        $entityManager = $eventArgs->getEntityManager();

        // Récupération de l'unité de travail
        $unitOfWork = $entityManager->getUnitOfWork();

        // Récupération des changements
        $scheduledEntityChanges = array(
            'insert' => $unitOfWork->getScheduledEntityInsertions(),
            'update' => $unitOfWork->getScheduledEntityUpdates(),
            'delete' => $unitOfWork->getScheduledEntityDeletions(),
        );

        // Initialisation des clés à supprimer
        $cacheIds = [];

        // Pour chaque type de changement
        foreach ($scheduledEntityChanges as $eventType => $entities) {
            // Pour chaque entité crée/modifiée/suprimée
            foreach ($entities as $entity) {
                // Récupération des champs modifiés en cas d'update
                $changeSet = 'update' == $eventType ? $unitOfWork->getEntityChangeSet($entity) : [];

                // Récupération de la classe de l'entité
                $entityClass = ClassUtils::getClass($entity);

                // Récupération des métadonnées de l'entité
                $entityClassMetadata = $entityManager->getClassMetadata($entityClass);

                // Initialisation des ID de cache de l'entité
                $entityCacheIds = [];

                // Pour chaque annotation d'invalidation du cache de la classe
                foreach ($this->annotationReader->getClassAnnotations($entityClassMetadata->getReflectionClass()) as $annotation) {
                    // Si c'est pas une annotation d'invalidation du cache
                    if (!($annotation instanceof CacheInvalidation)) {
                        // Annotation suivante
                        continue;
                    }

                    // Récupération de l'ID de cache
                    $entityCacheId = $annotation->id;

                    // Récupération des paramètres éventuels de l'ID
                    $cacheIdParameters = $this->extractCacheIdParameters($entityCacheId);

                    // Si on a des paramètre dans l'index
                    if (count($cacheIdParameters) > 0) {
                        // Si pas de paramètre dans la config
                        if (!$annotation->parameters) {
                            throw new CacheInvalidationException(sprintf('Missing parameters expressions for the cache id "%s" in class "%s".', $entityCacheId, $entityClass));
                        }

                        // Pour chaque paramètre de l'index
                        foreach ($cacheIdParameters as $name) {
                            // Si le paramètre n'est pas dans la configuration
                            if (!array_key_exists($name, $annotation->parameters)) {
                                throw new CacheInvalidationException(sprintf('Missing expression for parameter "%s" for the cache id "%s" in class "%s".', $name, $entityCacheId, $entityClass));
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
                            $entityCacheId = str_replace("\$$name", $paramValue, $entityCacheId);
                        }
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
                            throw new CacheInvalidationException('Unable to validate the cache id "%s" in class "%s" - %s', $entityCacheId, $entityClass, $e->getMessage());
                        }

                        // Si la validation n'est pas passée
                        if (!$validation) {
                            // Classe suivante
                            continue;
                        }
                    }

                    // Enregistrement de la clé
                    $entityCacheIds[] = $entityCacheId;
                }

                // Dédoublonnage éventuel
                $entityCacheIds = array_unique($entityCacheIds);

                // Si pas de clé
                if (!$entityCacheIds) {
                    // Entité suivante
                    continue;
                }

                // Récupération de l'ID de l'entité
                $entityId = 'insert' !== $eventType ? $entity->{sprintf('get%s', ucfirst($entityClassMetadata->getSingleIdentifierFieldName()))}() : 0;

                // Pour chque clé de l'entité
                foreach ($entityCacheIds as $key => $entityCacheId) {
                    // Si la clé n'est pas déjà enregistré pour suppression
                    if (!in_array($entityCacheId, $cacheIds)) {
                        // Enregistrement de la clé à supprimer
                        $cacheIds[] = $entityCacheId;

                        // Si on a un logger
                        if ($this->logger) {
                            // On log les clés à supprimer
                            $this->logger->info(sprintf('[%s #%d] [%s] Key to delete : %s', $entityClass, $entityId, $eventType, $entityCacheId));
                        }
                    }
                }
            }
        }

        // Si pas de clé à supprimer
        if (!$cacheIds) {
            // Si on a un logger
            if ($this->logger) {
                // On log la clé supprimée
                $this->logger->info('No key to delete.');
            }

            // Fin de la méthode
            return;
        }

        // Si on a un logger
        if ($this->logger) {
            // On log les clés à supprimer
            $this->logger->info(sprintf('All keys to delete : %s', implode(',', $cacheIds)));
        }

        // Récupération du cache de résultats
        $resultCache = $entityManager->getConfiguration()->getResultCacheImpl();

        // Compteur de suppressions
        $counter = 0;

        // Pour chaque index
        foreach ($cacheIds as $key => $cacheId) {
            // Si le cache contient l'index
            if ($resultCache->contains($cacheId)) {
                // Suppression
                $resultCache->delete($cacheId);

                // Incrémentation du compteur
                ++$counter;

                // Si on a un logger
                if ($this->logger) {
                    // On log la clé supprimée
                    $this->logger->info(sprintf('Deleted key : %s', $cacheId));
                }

                // Clé suivante
                continue;
            }

            // Si on a un logger
            if ($this->logger) {
                // On signale qu'on a pas trouvé la clé
                $this->logger->info(sprintf('Key not found : %s', $cacheId));
            }
        }

        // Si on a un logger
        if ($this->logger) {
            // On signale qu'on a pas trouvé la clé
            $this->logger->info(sprintf('Count of deleted keys : %d', $counter));
        }
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
