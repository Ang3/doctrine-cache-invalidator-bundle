<?php

namespace Ang3\Bundle\DoctrineCacheInvalidatorBundle\Helper;

/**
 * Entity changeSet object.
 *
 * @author Joanis ROUANET
 */
class EntityChangeSetHelper
{
    /**
     * Changes subject entity.
     *
     * @var object
     */
    protected $entity;

    /**
     * Entity change set.
     *
     * @var array
     */
    protected $changeSet;

    /**
     * Constructor of the object.
     *
     * @param object $entity
     * @param array  $changeSet
     */
    public function __construct($entity, array $changeSet)
    {
        $this->entity = $entity;
        $this->changeSet = $changeSet;
    }

    /**
     * Check if a property has been updated.
     *
     * @param string $propertyName
     *
     * @return bool
     */
    public function has($propertyName)
    {
        return array_key_exists($propertyName, $this->changeSet);
    }

    /**
     * Check if change set is related to at least one of given property names.
     *
     * @param array|string $propertyNames
     *
     * @return bool
     */
    public function hasAtLeastOne($propertyNames)
    {
        // Définition des noms de propriétés en tableau
        $propertyNames = (array) $propertyNames;

        // Pour chaque propriété modifiées
        foreach ($this->changeSet as $name => $values) {
            // Si le nom de la propriété est dans le tableau des propriétés recherchées
            if (in_array($name, $propertyNames)) {
                // Retour positif
                return true;
            }
        }

        // Retour négatif par défaut
        return false;
    }

    /**
     * Check if change set is related to all given property names.
     *
     * @param array|string $propertyNames
     *
     * @return bool
     */
    public function hasAll($propertyNames)
    {
        // Définition des noms de propriétés en tableau
        $propertyNames = (array) $propertyNames;

        // Pour chaque propriété modifiées
        foreach ($this->changeSet as $name => $values) {
            // Si le nom de la propriété est dans le tableau des propriétés recherchées
            if (!in_array($name, $propertyNames)) {
                // Retour négatif
                return false;
            }
        }

        // Retour positif par défaut
        return false;
    }

    /**
     * Get the old value of a property.
     *
     * @param string $propertyName
     *
     * @return mixed|null
     */
    public function getOldValue($propertyName)
    {
        // Si la propriété n'est pas déclarée
        if (!$this->has($propertyName)) {
            // Retour null
            return null;
        }

        // Retour de l'ancienne valeur
        return $this->changeSet[$propertyName][0];
    }

    /**
     * Get the old value of a property.
     *
     * @param string $propertyName
     *
     * @return mixed|null
     */
    public function getNewValue($propertyName)
    {
        // Si la propriété n'est pas déclarée
        if (!$this->has($propertyName)) {
            // Retour null
            return null;
        }

        // Retour de l'ancienne valeur
        return $this->changeSet[$propertyName][1];
    }
}
