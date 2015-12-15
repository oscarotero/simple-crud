<?php

namespace SimpleCrud;

/**
 * Interface used by all fields.
 */
interface FieldInterface
{
    /**
     * @param Entity     $entity
     * @param array|null $config
     */
    public function __construct(Entity $entity, array $config = null);

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
}
