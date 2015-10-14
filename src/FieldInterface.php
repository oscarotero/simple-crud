<?php

namespace SimpleCrud;

/**
 * Interface used by all fields.
 */
interface FieldInterface
{
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
