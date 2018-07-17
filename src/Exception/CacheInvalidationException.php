<?php

namespace Ang3\Bundle\DoctrineCacheInvalidatorBundle\Exception;

use RuntimeException;
use Throwable;

/**
 * @author Joanis ROUANET
 */
class CacheInvalidationException extends RuntimeException
{
    /**
     * Constructor of the exception.
     *
     * @param string|null    $message
     * @param Throwable|null $previous
     */
    public function __construct($message = null, Throwable $previous = null)
    {
        parent::__construct($message ?: 'An error occured.', 0, $previous);
    }
}
