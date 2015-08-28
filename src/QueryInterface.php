<?php
namespace SimpleCrud;

use PDOStatement;

/**
 * Interface used by all queries
 */
interface QueryInterface
{
    /**
     * Constructor
     *
     * @param Entity $entity
     */
    public function __construct(Entity $entity);

    /**
     * Run the query and return a statement with the result
     *
     * @return PDOStatement
     */
    public function run();

    /**
     * Build and return the query
     *
     * @return string
     */
    public function __toString();
}
