<?php
namespace SimpleCrud\Queries\Sqlite;

use SimpleCrud\Queries\Mysql;
use PDO;

/**
 * Manages a database query to get the fields names in Mysql databases
 */
class Fields extends Mysql\Fields
{
    /**
     * Run the query and return the fields
     * 
     * @return array
     */
    public function get()
    {
        $result = [];

        foreach ($this->run()->fetchAll(PDO::FETCH_ASSOC) as $field) {
            $result[$field['name']] = strtolower($field['type']);
        }

        return $result;
    }

    /**
     * Build and return the query
     * 
     * @return string
     */
    public function __toString()
    {
        return "pragma table_info(`{$this->entity->table}`)";
    }
}
