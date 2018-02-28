<?php

namespace Ang3\Bundle\DoctrineCacheInvalidatorBundle\Resolver;

/**
 * @author Joanis ROUANET
 */
interface ResolverInterface
{
    /**
     * Gets entity cache keys from mapping.
     *
     * @param object       $entity
     * @param array|string $change
     *
     * @return array
     */
    public function resolve($entity, $change);
}
