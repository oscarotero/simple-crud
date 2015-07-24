<?php
namespace SimpleCrud\Queries\Mysql;

use SimpleCrud\RowCollection;
use SimpleCrud\Row;
use SimpleCrud\Entity;
use SimpleCrud\SimpleCrudException;
use SimpleCrud\SimpleCrud;
use PDOStatement;
use PDO;

/**
 * Manages a database query to get the table names in Mysql databases
 */
class DbTables
{
    protected $db;

    public static function getInstance(SimpleCrud $db)
    {
        return new static($db);
    }

    public function __construct(SimpleCrud $db)
    {
        $this->db = $db;
    }

    /**
     * Run the query and return all values
     * 
     * @return PDOStatement
     */
    public function run()
    {
        return $this->db->execute((string) $this);
    }

    /**
     * Run the query and return the fields
     * 
     * @return array
     */
    public function get()
    {
        return $this->run()->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    /**
     * Build and return the query
     * 
     * @return string
     */
    public function __toString()
    {
        return 'SHOW TABLES';
    }
}
