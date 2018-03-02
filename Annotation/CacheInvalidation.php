<?php

namespace Ang3\Bundle\DoctrineCacheInvalidatorBundle\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class CacheInvalidation
{
    /**
     * The result cache ID.
     *
     * @Required
     *
     * @var string
     */
    public $id;

    /**
     * Values of potential dynamic ID parameters.
     *
     * @var array
     */
    public $parameters;

    /**
     * Optional validation expression.
     *
     * @var string
     */
    public $validation;
}
