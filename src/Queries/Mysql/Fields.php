<?php
namespace SimpleCrud\Queries\Mysql;

use SimpleCrud\Queries\BaseQuery;
use SimpleCrud\Entity;
use PDOStatement;

/**
 * Manages a database query to get the fields names in Mysql databases
 */
class Fields extends BaseQuery
{
    /**
     * Run the query and return all values
     *
     * @return PDOStatement
     */
    public function run()
    {
        return $this->entity->getDb()->execute((string) $this);
    }

    /**
     * Run the query and return the fields
     *
     * @return array
     */
    public function get()
    {
        $result = [];

        foreach ($this->run()->fetchAll() as $field) {
            preg_match('#^(\w+)#', $field['Type'], $matches);

            $result[$field['Field']] = $matches[1];
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
        return "DESCRIBE `{$this->entity->table}`";
    }
}
