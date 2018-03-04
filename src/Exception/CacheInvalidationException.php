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
     * @param string         $message
     * @param Throwable|null $previous
     */
    public function __construct($message = null, Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
