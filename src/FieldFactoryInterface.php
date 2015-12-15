<?php

namespace SimpleCrud;

/**
 * Interface used by the FieldFactory class.
 */
interface FieldFactoryInterface
{
    /**
     * Creates a new instance of a Field.
     *
     * @param Entity $entity
     * @param string $name
     * @param string $type
     *
     * @throws SimpleCrudException
     *
     * @return FieldInterface
     */
    public function get(Entity $entity, $name, $type);
}
