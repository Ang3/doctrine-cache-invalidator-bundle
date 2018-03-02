<?php

namespace Ang3\Bundle\DoctrineCacheInvalidatorBundle\EventListener;

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
     * Cache id resolver.
     *
     * @var CacheIdResolverInterface
     */
    protected $cacheIdResolver;

    /**
     * Logger.
     *
     * @var LoggerInterface|null
     */
    protected $logger;

    /**
     * Constructor of the listener.
     *
     * @param CacheIdResolverInterface $cacheIdResolver
     */
    public function __construct(CacheIdResolverInterface $cacheIdResolver)
    {
        $this->cacheIdResolver = $cacheIdResolver;
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

                // Récupération des clés à supprimer pour cette entité
                $entityCacheIds = $this->cacheIdResolver->resolve($entity, $eventType, $changeSet);

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
}
