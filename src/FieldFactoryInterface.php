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
     * @param Table  $table
     * @param string $name
     *
     * @throws SimpleCrudException
     *
     * @return Fields\Field
     */
    public function get(Table $table, $name);
}
