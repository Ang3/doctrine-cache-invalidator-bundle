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
     * @Required
     *
     * @var string
     */
    public $id;

    /**
     * @var array
     */
    public $parameters = [];

    /**
     * @var string|null
     */
    public $validation = null;
}