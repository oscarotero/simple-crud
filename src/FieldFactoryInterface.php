<?php

namespace SimpleCrud;

/**
 * Interface used by the FieldFactory class.
 */
interface FieldFactoryInterface
{
    /**
     * Detects the Field class and creates a new instance.
     *
     * @param Table  $table
     * @param string $name
     *
     * @throws SimpleCrudException
     *
     * @return Fields\Field
     */
    public function get(Table $table, $name);

    /**
     * Creates a new instance of a Field.
     *
     * @param Table  $table
     * @param string $className
     * @param string $name
     *
     * @throws SimpleCrudException
     *
     * @return Fields\Field
     */
    public function getInstance(Table $table, $className, $name);
}
