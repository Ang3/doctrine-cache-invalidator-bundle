<?php

namespace Ang3\Bundle\DoctrineCacheInvalidatorBundle\Resolver;

/**
 * @author Joanis ROUANET
 */
interface CacheIdResolverInterface
{
    /**
     * Gets entity cache ids from entity annotation and event metadata.
     *
     * @param object       $entity
     * @param string       $eventType
     * @param array|string $changeSet
     *
     * @return array
     */
    public function resolve($entity, $eventType, array $changeSet = []);
}
