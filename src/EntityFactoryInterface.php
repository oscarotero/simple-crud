<?php

namespace SimpleCrud;

/**
 * Interface used by the entity factory.
 */
interface EntityFactoryInterface
{
    /**
     * Set the database used for the entities.
     *
     * @param SimpleCrud $db
     *
     * @return self
     */
    public function setDb(SimpleCrud $db);

    /**
     * Check whether or not an Entity is instantiable.
     *
     * @param string $name
     *
     * @return bool
     */
    public function has($name);

    /**
     * Creates a new instance of an Entity.
     *
     * @param string $name
     *
     * @throws SimpleCrudException
     *
     * @return Entity
     */
    public function get($name);
}
