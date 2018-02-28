<?php

namespace Ang3\Bundle\DoctrineCacheInvalidatorBundle\Tests\Resolver;

use DateTime;
use Exception;
use Ang3\Bundle\DoctrineCacheInvalidatorBundle\Resolver\EntityResolver;
use Ang3\Bundle\PhpunitBundle\Tests\UnitTest;

/**
 * Cache id resolver test.
 *
 * @coversDefaultClass \Ang3\Bundle\DoctrineCacheInvalidatorBundle\Resolver\EntityResolver
 *
 * @author Joanis ROUANET
 */
class EntityResolverTest extends UnitTest
{
    /**
     * Tested class.
     *
     * @var EntityResolver
     */
    protected $entityResolver;

    /**
     * Fonction de démarrage.
     */
    public function setUp()
    {
        parent::setUp();

        $this->entityResolver = $this->newInstance(EntityResolver::class);
    }

    /**
     * Provider for the test : testExtractKeyParameters.
     *
     * @return array
     */
    public function extractKeyParametersProvider()
    {
        return [
            ['foo', []],
            ['foo..bar', []],
            ['foo.$.bar', []],
            ['foo.$bar', ['bar']],
            ['foo.$$', []],
            ['foo.$$.bar', []],
            ['foo.$$1', ['$1']],
            ['foo.$$test', ['$test']],
            ['foo.$$1.bar', ['$1']],
            ['foo.$$test.bar', ['$test']],
            ['foo.$$test_1.bar', ['$test_1']],
            ['foo.$$', []],
            ['foo.$1.$2', ['1', '2']],
            ['foo.$1_$2', ['1_', '2']],
            ['$foo.$1_$2', ['foo', '1_', '2']],
        ];
    }

    /**
     * @covers ::extractKeyParameters
     *
     * @dataProvider extractKeyParametersProvider
     */
    public function testExtractKeyParameters($key, array $parameters)
    {
        // Assertions
        $this->assertEquals($parameters, $this->invokeMethod($this->entityResolver, 'extractKeyParameters', [$key]));
    }

    /**
     * @expectedException \Ang3\Bundle\DoctrineCacheInvalidatorBundle\Exception\MappingException
     * @expectedCode 0
     */
    public function testResolveFailed_ClassNotFound()
    {
        // Création du resolver
        $this->entityResolver = new EntityResolver([
            'basic' => [
                'Fake\\FakeClass' => [],
            ],
        ]);

        // Création de l'entité de test (une date)
        $entity = new DateTime('2000-05-04 03:02:01');

        // Exception
        $this->entityResolver->resolve($entity, 'insert');
    }

    /**
     * @expectedException \Ang3\Bundle\DoctrineCacheInvalidatorBundle\Exception\MappingException
     * @expectedCode 1
     */
    public function testResolveFailed_MissingParameters()
    {
        // Création du resolver
        $this->entityResolver = new EntityResolver([
            'params_$foo.$bar' => [
                DateTime::class => [],
            ],
        ]);

        // Création de l'entité de test (une date)
        $entity = new DateTime('2000-05-04 03:02:01');

        // Exception
        $this->entityResolver->resolve($entity, 'insert');
    }

    /**
     * @expectedException \Ang3\Bundle\DoctrineCacheInvalidatorBundle\Exception\MappingException
     * @expectedCode 2
     */
    public function testResolveFailed_MissingParameter()
    {
        // Création du resolver
        $this->entityResolver = new EntityResolver([
            'params_$foo.$bar' => [
                DateTime::class => [
                    'parameters' => [
                        'foo' => 'bar',
                    ],
                ],
            ],
        ]);

        // Création de l'entité de test (une date)
        $entity = new DateTime('2000-05-04 03:02:01');

        // Exception
        $this->entityResolver->resolve($entity, 'insert');
    }

    /**
     * @expectedException \Ang3\Bundle\DoctrineCacheInvalidatorBundle\Exception\MappingException
     * @expectedCode 3
     */
    public function testResolveFailed_ParamConvert()
    {
        // Création du resolver
        $this->entityResolver = new EntityResolver([
            'params_$foo.$bar' => [
                DateTime::class => [
                    'parameters' => [
                        'foo' => 'bar',
                        'bar' => 'foo',
                    ],
                ],
            ],
        ]);

        // Création de l'entité de test (une date)
        $entity = new DateTime('2000-05-04 03:02:01');

        // Exception
        $this->entityResolver->resolve($entity, 'insert');
    }

    /**
     * @expectedException \Ang3\Bundle\DoctrineCacheInvalidatorBundle\Exception\MappingException
     * @expectedCode 4
     */
    public function testResolveFailed_Validation()
    {
        // Création du resolver
        $this->entityResolver = new EntityResolver([
            'basic' => [
                DateTime::class => [
                    'validation' => 'these',
                ],
            ],
        ]);

        // Création de l'entité de test (une date)
        $entity = new DateTime('2000-05-04 03:02:01');

        // Exception
        $this->entityResolver->resolve($entity, 'insert');
    }

    /**
     * @covers ::resolve
     *
     * @depends testExtractKeyParameters
     * @depends testResolveFailed_MissingParameters
     * @depends testResolveFailed_MissingParameter
     * @depends testResolveFailed_ParamConvert
     * @depends testResolveFailed_Validation
     */
    public function testResolve()
    {
        // Assertions avec entité lambda
        $this->assertEquals([], $this->entityResolver->resolve(new Exception(), 'insert'));
        $this->assertEquals([], $this->entityResolver->resolve(new Exception(), 'update'));
        $this->assertEquals([], $this->entityResolver->resolve(new Exception(), 'delete'));

        // Création du resolver
        $this->entityResolver = new EntityResolver([
            'basic' => [
                DateTime::class => [],
            ],
            'params_$foo.$bar' => [
                DateTime::class => [
                    'parameters' => [
                        'foo' => 'this.format("Y")',
                        'bar' => 'this.format("m")',
                    ],
                ],
            ],
            'special' => [
                DateTime::class => [
                    'validation' => 'eventType == "update" and this.format("m") == 5',
                ],
            ],
            'full.$year' => [
                DateTime::class => [
                    'parameters' => [
                        'year' => 'this.format("Y")',
                    ],
                    'validation' => 'eventType == "update" and this.format("n") == 1',
                ],
            ],
        ]);

        // Création de l'entité de test (une date)
        $entity = new DateTime('2000-05-04 03:02:01');

        // Assertions simples
        $this->assertEquals(['basic', 'params_2000.05'], $this->entityResolver->resolve($entity, 'insert'));
        $this->assertEquals(['basic', 'params_2000.05', 'special'], $this->entityResolver->resolve($entity, 'update'));
        $this->assertEquals(['basic', 'params_2000.05'], $this->entityResolver->resolve($entity, 'delete'));

        // Création de l'entité de test (une date au mois de janvier)
        $entity = new DateTime('2000-01-04 03:02:01');

        // Assertions avec paramètres
        $this->assertEquals(['basic', 'params_2000.01'], $this->entityResolver->resolve($entity, 'insert'));
        $this->assertEquals(['basic', 'params_2000.01', 'full.2000'], $this->entityResolver->resolve($entity, 'update'));
        $this->assertEquals(['basic', 'params_2000.01'], $this->entityResolver->resolve($entity, 'delete'));
    }
}
