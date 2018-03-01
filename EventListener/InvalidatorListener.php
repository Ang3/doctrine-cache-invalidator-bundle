<?php

namespace Ang3\Bundle\DoctrineCacheInvalidatorBundle\EventListener;

use Ang3\Bundle\DoctrineCacheInvalidatorBundle\Exception\CacheInvalidationException;
use Ang3\Bundle\DoctrineCacheInvalidatorBundle\Resolver\CacheIdResolver;
use Ang3\Bundle\DoctrineCacheInvalidatorBundle\Resolver\CacheIdResolverInterface;
use Doctrine\Common\Util\ClassUtils;
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
     * Cache invalidator parameters.
     *
     * @var array|null
     */
    protected $parameters;

    /**
     * Logger.
     *
     * @var LoggerInterface|null
     */
    protected $logger;

    /**
     * Constructor of the listener.
     *
     * @param array                $parameters
     * @param LoggerInterface|null $logger
     */
    public function __construct(array $parameters = [], LoggerInterface $logger = null)
    {
        $this->parameters = $parameters;
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

        // Définition de resolver à utiliser
        $resolverClass = array_key_exists('resolver_class', $this->parameters) ? ($this->parameters['resolver_class'] ?: CacheIdResolver::class) : CacheIdResolver::class;

        // Si le résolveur n'implémente pas l'interface requise
        if (!(ClassUtils::newReflectionClass($resolverClass)->newInstanceWithoutConstructor() instanceof CacheIdResolverInterface)) {
            throw new CacheInvalidationException('The resolver class "%s" must implements interface "%s".', $resolverClass, CacheIdResolverInterface::class);
        }

        // Création du résolveur d'ID de cache
        $cacheIdResolver = new $resolverClass($entityManager);

        // Récupération des changements
        $scheduledEntityChanges = array(
            'insert' => $unitOfWork->getScheduledEntityInsertions(),
            'update' => $unitOfWork->getScheduledEntityUpdates(),
            'delete' => $unitOfWork->getScheduledEntityDeletions(),
        );

        // Initialisation des clés à supprimer
        $cacheKeys = [];

        // Pour chaque type de changement
        foreach ($scheduledEntityChanges as $eventType => $entities) {
            // Pour chaque entité crée/modifiée/suprimée
            foreach ($entities as $entity) {
                // Récupération des champs modifiés en cas d'update
                $changeSet = 'update' == $eventType ? $unitOfWork->getEntityChangeSet($entity) : [];

                // Récupération des clés à supprimer pour cette entité
                $entityCacheIds = $cacheIdResolver->resolve($entity, $eventType, $changeSet);

                // Si pas de clé
                if (!$entityCacheIds) {
                    // Entité suivante
                    continue;
                }

                // Récupération de la classe de l'entité
                $entityClass = ClassUtils::getClass($entity);

                // Récupération de l'ID de l'entité
                $entityId = 'insert' !== $eventType ? $entity->{sprintf('get%s', ucfirst($entityManager->getClassMetadata($entityClass)->getSingleIdentifierFieldName()))}() : 0;

                // Pour chque clé de l'entité
                foreach ($entityCacheIds as $key => $entityCacheId) {
                    // Si la clé n'est pas déjà enregistré pour suppression
                    if (!in_array($entityCacheId, $cacheKeys)) {
                        // Enregistrement de la clé à supprimer
                        $cacheKeys[] = $entityCacheId;

                        // Si on a un logger
                        if ($this->logger) {
                            // On log les clés à supprimer
                            $this->logger->info(sprintf('[%s #%d] [%s] Key to delete : %s', $entityClass, $entityId, $eventType, $entityCacheId));
                        }
                    }
                }
            }
        }

        // Récupération du cache de résultats
        $resultCache = $entityManager->getConfiguration()->getResultCacheImpl();

        // Si pas de clé à supprimer
        if (!$cacheKeys) {
            // On log la clé supprimée
            $this->logger->info('No key to delete.');

            // Fin de la méthode
            return;
        }

        // Si on a un logger
        if ($this->logger) {
            // On log les clés à supprimer
            $this->logger->info(sprintf('All keys to delete : %s', implode(',', $cacheKeys)));
        }

        // Compteur de suppressions
        $counter = 0;

        // Pour chaque index
        foreach ($cacheKeys as $key => $cacheId) {
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
}
