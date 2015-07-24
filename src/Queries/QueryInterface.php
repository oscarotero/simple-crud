<?php
namespace SimpleCrud\Queries;

use SimpleCrud\Entity;
use SimpleCrud\SimpleCrudException;
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
     * Executes the query and return the result
     * 
     * @param Entity $entity
     * @param array  $args
     * 
     * @throws SimpleCrudException
     * 
     * @return mixed
     */
    public static function execute(Entity $entity, array $args);

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
