<?php
namespace SimpleCrud\Query\Mysql;

use SimpleCrud\RowCollection;
use SimpleCrud\Row;
use SimpleCrud\Entity;
use SimpleCrud\SimpleCrudException;
use PDOStatement;
use PDO;

/**
 * Manages a database query to get the fields names in Mysql databases
 */
class Fields
{
    protected $entity;

    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
    }

    /**
     * Run the query and return all values
     * 
     * @return RowCollection
     */
    public function run()
    {
        $result = [];

        $statement = $this->entity->getAdapter->execute((string) $this);

        foreach ($statement->fetchAll() as $field) {
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
