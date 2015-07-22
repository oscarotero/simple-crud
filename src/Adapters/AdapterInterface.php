<?php
namespace SimpleCrud\Adapters;

use PDOStatement;

/**
 * Interface used by all adapters.
 */
interface AdapterInterface
{
    /**
     * Execute a query and returns the statement object with the result.
     *
     * @param string     $query
     * @param null|array $marks
     *
     * @throws Exception On error
     *
     * @return PDOStatement
     */
    public function execute($query, array $marks = null);

    /**
     * Execute a callable inside a transaction.
     *
     * @param callable $callable The function with all operations
     *
     * @throws Exception On error
     *
     * @return mixed The value returned by the callable
     */
    public function executeTransaction(callable $callable);

    /**
     * Returns the last inserted id.
     *
     * @return integer
     */
    public function lastInsertId();

    /**
     * Returns all tables of the database.
     *
     * @return array
     */
    public function getTables();

    /**
     * Returns the database type
     *
     * @return string
     */
    public function getType();

    /**
     * Saves a new attribute
     * 
     * @param string $name
     * @param mixed  $value
     * 
     * @return $this
     */
    public function setAttribute($name, $value);

    /**
     * Returns an attribute
     * 
     * @param string $name
     * 
     * @return null|mixed
     */
    public function getAttribute($name);
}
