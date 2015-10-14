<?php

namespace SimpleCrud;

use JsonSerializable;

/**
 * Interface used by Row and RowCollection.
 */
interface RowInterface extends JsonSerializable
{
    /**
     * Generate an array with all values and subvalues of the row.
     *
     * @param bool  $keysAsId       If the keys of the arrays are the ids
     * @param array $parentEntities Parent entities of this row. It's used to avoid infinite recursions
     *
     * @return array
     */
    public function toArray($keysAsId = false, array $parentEntities = array());

    /**
     * Return the entity.
     *
     * @return Entity
     */
    public function getEntity();

    /**
     * Returns an attribute.
     *
     * @param string $name
     *
     * @return null|mixed
     */
    public function getAttribute($name);

    /**
     * Register a new custom method.
     *
     * @param string   $name
     * @param callable $callable
     *
     * @return self
     */
    public function registerMethod($name, callable $callable);

    /**
     * Register a new custom property.
     *
     * @param string   $name
     * @param callable $callable
     *
     * @return self
     */
    public function registerProperty($name, callable $callable);
}
