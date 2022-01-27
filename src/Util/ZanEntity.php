<?php


namespace Zan\CommonBundle\Util;


class ZanEntity
{
    /**
     * Determines if two entities should be considered the same database record
     *
     * NOTE:
     *   - entities MUST implement a getId() method for this to work
     *   - does not work on composite primary keys
     *
     * This handles the following scenarios:
     *
     * 1. Two existing entities are being compared
     * 2. A new entity is being compared to itself (should return true)
     * 3. Two new entities are being compared to each other (should return false)
     *
     * @return bool
     */
    public static function isSame(?object $a, ?object $b)
    {
        // empty values are never equal
        if (!$a || !$b) return false;

        // exact object matches are always equal
        if ($a === $b) return true;

        // Objects of different classes are never equal
        if (get_class($a) !== get_class($b)) return false;

        // Verify that objects have getId() methods
        if (!method_exists($a, 'getId') || !method_exists($b, 'getId')) throw new \InvalidArgumentException('ZanEntity::isSame requires that objects implement getId');

        // empty IDs are never equal (including 0 since this should never be a database ID)
        if (!$a->getId() || !$b->getId()) return false;

        // equal database IDs should be considered the same entity
        if ($a->getId() === $b->getId()) return true;

        // nothing matches between the entities
        return false;
    }
}