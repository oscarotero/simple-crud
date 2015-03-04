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
     * @return PDOStatement The result
     */
    public function execute($query, array $marks = null);

    /**
     * Execute a callable inside a transaction.
     *
     * @param callable $callable The function with all operations
     *
     * @throws Exception On error
     *
     * @return mixed The callable returned value
     */
    public function executeTransaction(callable $callable);

    /**
     * Returns the last inserted id.
     *
     * @return integer
     */
    public function lastInsertId();

    /**
     * Executes a SELECT.
     *
     * @param array      $fields
     * @param null|array $joins
     * @param mixed      $where
     * @param null|array $marks
     * @param mixed      $orderBy
     * @param mixed      $limit
     *
     * @throws Exception On error
     *
     * @return PDOStatement The result
     */
    public function executeSelect(array $fields, array $joins = null, $where = null, array $marks = null, $orderBy = null, $limit = null);

    /**
     * Executes a COUNT.
     *
     * @param string     $table
     * @param mixed      $where
     * @param null|array $marks
     * @param mixed      $limit
     *
     * @throws Exception On error
     *
     * @return integer
     */
    public function count($table, $where = null, array $marks = null, $limit = null);

    /**
     * Executes an INSERT.
     *
     * @param string  $table
     * @param array   $data
     * @param boolean $duplicateErrors
     *
     * @throws Exception On error
     *
     * @return boolean
     */
    public function insert($table, array $data, $duplicateKeyErrors = false);

    /**
     * Executes an UPDATE.
     *
     * @param string     $table
     * @param array      $data
     * @param mixed      $where
     * @param null|array $marks
     * @param mixed      $limit
     *
     * @throws Exception On error
     *
     * @return boolean
     */
    public function update($table, array $data, $where = null, array $marks = null, $limit = null);

    /**
     * Executes a DELETE.
     *
     * @param string     $table
     * @param mixed      $where
     * @param null|array $marks
     * @param mixed      $limit
     *
     * @throws Exception On error
     *
     * @return boolean
     */
    public function delete($table, $where = null, array $marks = null, $limit = null);

    /**
     * Returns a list of all fields in a table.
     *
     * @param string $table
     *
     * @return array The fields with the format [name => type]
     */
    public function getFields($table);

    /**
     * Returns all tables of the database.
     *
     * @return array
     */
    public function getTables();
}
