<?php

namespace Ang3\Bundle\DoctrineCacheInvalidatorBundle\Exception;

use InvalidArgumentException;
use Throwable;

/**
 * @author Joanis ROUANET
 */
class MappingException extends InvalidArgumentException
{
    /**
     * Cache key.
     *
     * @var string
     */
    protected $key;

    /**
     * Cache class.
     *
     * @var string
     */
    protected $class;

    /**
     * Constructor of the exception.
     *
     * @param string         $key
     * @param string         $class
     * @param string|null    $message
     * @param int|null       $code
     * @param Throwable|null $previous
     */
    public function __construct($key, $class, $message = null, $code = 0, Throwable $previous = null)
    {
        // Hydratation
        $this->key = (string) $key;
        $this->class = (string) $class;

        // Construction de l'exception parente
        parent::__construct(
			sprintf('The configuration of the cache key "%s" for the class "%s" is invalid - %s%s - Check the mapping file.',
				$key, $class, $message, $previous ? sprintf(' - %s ', $previous->getMessage()) : null
			),
			$code,
			$previous
		);
    }

    /**
     * Gets key.
     *
     * @param string $key
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Gets class.
     *
     * @param string $class
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }
}
