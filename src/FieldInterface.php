<?php

namespace SimpleCrud;

/**
 * Interface used by all fields.
 */
interface FieldInterface
{
    /**
     * Set the configuration of this field
     *
     * @param array $config
     */
    public function setConfig(array $config);

    /**
     * Returns configuration of this field
     *
     * @return array
     */
    public function getConfig();

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
