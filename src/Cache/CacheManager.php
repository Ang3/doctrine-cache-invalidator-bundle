<?php

namespace Ang3\Bundle\DoctrineCacheInvalidatorBundle\Cache;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Cache Invalidator listener.
 *
 * @author Joanis ROUANET
 */
class CacheManager
{
    /**
     * Doctrine entity manager.
     *
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * Constructor of the cache manager.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
}
