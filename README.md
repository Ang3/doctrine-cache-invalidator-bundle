DoctrineCacheInvalidatorBundle
==============================

[![Build Status](https://travis-ci.org/Ang3/DoctrineCacheInvalidatorBundle.svg?branch=master)](https://travis-ci.org/Ang3/DoctrineCacheInvalidatorBundle) [![Latest Stable Version](https://poser.pugx.org/ang3/doctrine-cache-invalidator-bundle/v/stable)](https://packagist.org/packages/ang3/doctrine-cache-invalidator-bundle) [![Latest Unstable Version](https://poser.pugx.org/ang3/doctrine-cache-invalidator-bundle/v/unstable)](https://packagist.org/packages/ang3/doctrine-cache-invalidator-bundle) [![Total Downloads](https://poser.pugx.org/ang3/doctrine-cache-invalidator-bundle/downloads)](https://packagist.org/packages/ang3/doctrine-cache-invalidator-bundle)

A [Symfony](https://symfony.com) bundle to manage doctrine result cache invalidations.

## Installation

You can install the bundle in 2 different ways:

- Install it via Composer (ang3/doctrine-cache-invalidator-bundle on Packagist)
- Use the official Git repository (https://github.com/Ang3/DoctrineCacheInvalidatorBundle)

### Enable the bundle

To start using the bundle, register the bundle in your application's kernel class:

```php
// app/AppKernel.php
class AppKernel extends Kernel
{
  public function registerBundles()
  {
    $bundles = [
      // ...
      new Ang3\Bundle\DoctrineCacheInvalidatorBundle\Ang3DoctrineCacheInvalidatorBundle(),
      // ...
    ];
  }
}
```

### Configuration reference

```yaml
# app/config.yml
ang3_doctrine_cache_invalidator:
  mapping: '<MAPPING_FILE_PATH>' # The required path of the mapping file (example : '@AppBundle/Resources/config/cache/mapping.yml')
  logger: ~ # Service instance of Psr\Log\LoggerInterface - default: null
```

### Mapping

Create the mapping file in the location of your configuration (in this example ```@AppBundle/Resources/config/cache/mapping.yml```). The file must have this minimal configuration :

```yaml
# src/AppBundle/Resouces/config/cache/mapping.yml
mapping:
  # Content here...
```

## Basic Usage

### Context

Result cache ID registered in a method of repository class ```AppBundle\Entity\MyRepository```.

```php
// src/AppBundle/Repository/MyRepository.php
$qb
  // ...
  // Get the query
  ->getQuery()
  // Register an ID to invalidate results
  ->setResultCacheId('my_custom_id')
  // Get the result as you want
  ->getResult() # Or what ever
;
```

### Mapping

This mapping file contains all conditions of invalidations by cache IDs and classes. For each flushed entity by the *default entity manager*, the mapping allows you to determine wich keys should be deleted.

**All used expressions** in the mapping are evaluated by the component [symfony/expression-language](https://packagist.org/packages/symfony/expression-language).

```yaml
# src/AppBundle/Resouces/config/cache/mapping.yml
mapping:
  cache_id_1: # ID of the result cache
    AppBundle\YourClass1:
      parameters: <EXPRESSION> # Optional if no parameter in the cache ID
      validation: <EXPRESSION> # Optional - Validation of the class
    AppBundle\YourClass2:
      parameters: <EXPRESSION> # Optional if no parameter in the cache ID
      validation: <EXPRESSION> # Optional - Validates of the class
    # ...
  cache_id_2:
    AppBundle\YourClass1:
      parameters: <EXPRESSION> # Optional if no parameter in the cache ID
      validation: <EXPRESSION> # Optional - Validation of the class
  # ...
```

### Dynamic ID and parameters

In case of dynamic ID, you can register variables like PHP (with ```$```), but you have to specify the related expression under the "parameters" section of the node. I suggest you to use the dot ```.``` to end the variable name.

```yaml
# src/AppBundle/Resouces/config/cache/mapping.yml
mapping:
  my_cache_id_$param_1:
    AppBundle\YourClass1:
      parameters:
        param_1: <EXPRESSION>
```

### Validation

You can submit the entity to a validation during process. You just have to specify an expression. The cache ID is deleted if the expression returns *TRUE*.

```yaml
# src/AppBundle/Resouces/config/cache/mapping.yml
mapping:
  cache_id_$param_1:
    AppBundle\YourClass1:
      parameters:
        param_1: <EXPRESSION>
        validation: <EXPRESSION> # If the expression results true, the ID is deleted
```

### Expressions

For each expression these variables are passed during the evaluation :

- ```this``` the added/edited/deleted entity
- ```eventType``` 'insert', 'update' ou 'delete' (string)
- ```changeSet``` entity updated fields values in case of update (empty array if eventType is equal to 'insert' or 'delete')

## Todo

- Use more than the default entity manager.