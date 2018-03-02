DoctrineCacheInvalidatorBundle
==============================

[![Build Status](https://travis-ci.org/Ang3/DoctrineCacheInvalidatorBundle.svg?branch=master)](https://travis-ci.org/Ang3/DoctrineCacheInvalidatorBundle) [![Latest Stable Version](https://poser.pugx.org/ang3/doctrine-cache-invalidator-bundle/v/stable)](https://packagist.org/packages/ang3/doctrine-cache-invalidator-bundle) [![Latest Unstable Version](https://poser.pugx.org/ang3/doctrine-cache-invalidator-bundle/v/unstable)](https://packagist.org/packages/ang3/doctrine-cache-invalidator-bundle) [![Total Downloads](https://poser.pugx.org/ang3/doctrine-cache-invalidator-bundle/downloads)](https://packagist.org/packages/ang3/doctrine-cache-invalidator-bundle)

A [Symfony](https://symfony.com) bundle to manage doctrine result cache invalidations.

Installation
============

Step 1: Download the Bundle
---------------------------

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require ang3/doctrine-cache-invalidator-bundle
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Step 2: Enable the Bundle
-------------------------

Then, enable the bundle by adding it to the list of registered bundles
in the `app/AppKernel.php` file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
  public function registerBundles()
  {
    $bundles = array(
      // ...
      new Ang3\Bundle\DoctrineCacheInvalidatorBundle\Ang3DoctrineCacheInvalidatorBundle(),
    );

    // ...
  }

  // ...
}
```

Step 3: Configure your app
-------------------------

No config is required, but you can configure a specific logger or cache ids resolver class:

```yaml
# app/config/config.yml
ang3_doctrine_cache_invalidator:
  logger: ~ # An optionnal logger ID
  resolver_class: 'Ang3\DoctrineCacheInvalidatorBundle\Resolver\CacheIdResolver' # default value
```

Usage
=====

## Context

Suppose you have a result cache ID registered in the repository class ```AppBundle\Repository\MyRepository```.

```php
// src/AppBundle/Repository/MyRepository.php

// ...
$qb
  // ...
  // Get the query
  ->getQuery()
  // Register an ID to invalidate results
  ->setResultCacheId('my_custom_id')
  // Get the result as you want
  ->getResult() # Or what ever
;
// ...
```

## Invalidation

The invalidation process is during the "flush" entity operation. On each entity, the resolver is called so as to define potential cache indexes to delete. To do that, you just have to write an annotation on concerned entities and configure it.

```php
// src/AppBundle/Entity/EntityA.php

// Do not forget the "use" statement
use Ang3\DoctrineCacheInvalidatorBundle\Annotation\CacheInvalidation;
// ...

/**
 * @CacheInvalidation(id="my_custom_id")
 */
class EntityA
{
  // ...
}
```

## Options

The [CacheInvalidation annotation](https://github.com/Ang3/DoctrineCacheInvalidatorBundle/blob/master/Annotation/CacheInvalidation.php) has three parameters :

- ```id``` (required) : result cache id to delete
- ```parameters``` (optional) : associative array of potential ID parameters (the value is represented by an **expression**)
- ```validation``` (optional) : an other **expression** to validate a specific entity

### Dynamic ID and parameters

In case of dynamic result cache ID, you can register variables like PHP (with ```$```), but you have to specify the related expression under the "parameters" option. I suggest you to use the dot ```.``` to end the variable name:

```php
// src/AppBundle/Entity/EntityA.php

// Do not forget the "use" statement
use Ang3\DoctrineCacheInvalidatorBundle\Annotation\CacheInvalidation;
// ...

/**
 * @CacheInvalidation(id="my_custom_.$id", parameters={"id":"this.getId()"})
 */
class EntityA
{
  // ...
}
```

### Validation

You can also submit the entity to a validation during process. You just have to specify an expression so to as to return a boolean value. The result cache ID is deleted if the expression returns *TRUE* **or equivalent** (castable).

```php
// src/AppBundle/Entity/EntityA.php

// Do not forget the "use" statement
use Ang3\DoctrineCacheInvalidatorBundle\Annotation\CacheInvalidation;
// ...

/**
 * @CacheInvalidation(id="my_custom_.$id", parameters={"id":"this.getId()"}, validation="eventType == 'update'")
 */
class EntityA
{
  // ...
}
```

### Expressions

**All used expressions** are evaluated by the component [symfony/expression-language](https://packagist.org/packages/symfony/expression-language).

For each expression these variables are passed during the evaluation :

- ```this``` (object) the added/edited/deleted entity
- ```eventType``` (string) 'insert', 'update' ou 'delete'
- ```changeSet``` (array) entity updated fields values in case of update (empty array if eventType is equal to 'insert' or 'delete')

Todo
====

- [ ] Use more than the default entity manager (get doctrine registry in the listener, then add 'entity_manager' option in the annotation and check entity manager during the flush operation) !