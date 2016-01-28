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
     * @param array  $config
     *
     * @throws SimpleCrudException
     *
     * @return FieldInterface
     */
    public function get(Entity $entity, array $config);
}
