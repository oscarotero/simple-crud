<?php

namespace SimpleCrud;

/**
 * Interface used by the QueryFactory class.
 */
interface QueryFactoryInterface
{
    /**
     * Creates a new instance of a Query.
     *
     * @param Table  $table
     * @param string $name
     *
     * @throws SimpleCrudException
     *
     * @return Queries\Query
     */
    public function get(Table $table, $name);
}
