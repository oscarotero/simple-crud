<?php
namespace SimpleCrud\Queries;

use SimpleCrud\Entity;
use PDOStatement;

/**
 * Interface used by all queries
 */
interface QueryInterface
{
    /**
     * Creates a query instance
     *
     * @param Entity $entity
     *
     * @return QueryInterface
     */
    public static function getInstance(Entity $entity);

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
