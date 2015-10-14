<?php

namespace SimpleCrud;

/**
 * Interface used by the QueryFactory class.
 */
interface QueryFactoryInterface
{
    /**
     * Set the entity used for the queries.
     *
     * @param Entity $entity
     *
     * @return self
     */
    public function setEntity(Entity $entity);

    /**
     * Check whether or not a Query class is instantiable.
     *
     * @param string $name
     *
     * @return bool
     */
    public function has($name);

    /**
     * Creates a new instance of a Field.
     *
     * @param string $name
     *
     * @throws SimpleCrudException
     *
     * @return FieldInterface
     */
    public function get($name);
}
