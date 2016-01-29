<?php

namespace SimpleCrud;

/**
 * Interface used by all fields.
 */
interface FieldInterface
{
    /**
     * @param Entity $entity
     * @param array  $config
     */
    public function __construct(Entity $entity, array $config);

    /**
     * Convert the data before insert into the database.
     *
     * @param mixed $data
     *
     * @return mixed
     */
    public function dataToDatabase($data);

    /**
     * Convert the data after read from the database.
     *
     * @param mixed $data
     *
     * @return mixed
     */
    public function dataFromDatabase($data);

    /**
     * Returns a config value.
     * 
     * @param string $name
     * 
     * @return mixed
     */
    public function getConfig($name);

    /**
     * Set a config value.
     * 
     * @param string $name
     * @param mixed  $value
     * 
     * @return mixed
     */
    public function setConfig($name, $value);
}
