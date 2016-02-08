<?php

namespace SimpleCrud;

/**
 * Interface used by the table factory.
 */
interface TableFactoryInterface
{
    /**
     * Creates a new instance of a Table.
     *
     * @param SimpleCrud $db
     * @param string     $name
     *
     * @throws SimpleCrudException
     *
     * @return Table
     */
    public function get(SimpleCrud $db, $name);
}
