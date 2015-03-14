<?php
namespace SimpleCrud;

/**
 * Interface used by Row and RowCollection.
 */
interface RowInterface
{
    /**
     * Generate an array with all values and subvalues of the row.
     *
     * @param boolean $keysAsId       If the keys of the arrays are the ids
     * @param array   $parentEntities Parent entities of this row. It's used to avoid infinite recursions
     *
     * @return array
     */
    public function toArray($keysAsId = false, array $parentEntities = array());

    /**
     * Return the entity
     * 
     * @return Entity
     */
    public function getEntity();

    /**
     * Return the adapter
     * 
     * @return Adapters\AdapterInterface
     */
    public function getAdapter();
}
