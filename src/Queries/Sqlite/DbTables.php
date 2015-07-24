<?php
namespace SimpleCrud\Queries\Sqlite;

use SimpleCrud\Queries\Mysql;

/**
 * Manages a database query to get the table names in Sqlite databases
 */
class DbTables extends Mysql\DbTables
{
    /**
     * Build and return the query
     * 
     * @return string
     */
    public function __toString()
    {
        return 'SELECT name FROM sqlite_master WHERE type="table"';
    }
}
