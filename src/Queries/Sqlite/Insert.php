<?php
namespace SimpleCrud\Queries\Sqlite;

use SimpleCrud\Queries\Mysql;

/**
 * Manages a database insert query in Mysql databases
 */
class Insert extends Mysql\Insert
{
    /**
     * Build and return the query
     * 
     * @return string
     */
    public function __toString()
    {
        if (empty($this->data) || !$this->duplications) {
            return parent::__toString();
        }

        $fields = array_keys($this->data);

        $query = "INSERT OR REPLACE INTO `{$this->entity->table}`";
        $query .= ' (`'.implode('`, `', $fields).'`)';
        $query .= ' VALUES (:'.implode(', :', $fields).')';

        return $query;
    }
}
