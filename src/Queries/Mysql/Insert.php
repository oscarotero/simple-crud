<?php
namespace SimpleCrud\Queries\Mysql;

use SimpleCrud\RowCollection;
use SimpleCrud\Row;
use SimpleCrud\Entity;
use SimpleCrud\SimpleCrudException;
use PDOStatement;
use PDO;

/**
 * Manages a database insert query in Mysql databases
 */
class Insert
{
    protected $entity;

    protected $data = [];
    protected $duplications;

    public static function getInstance(Entity $entity)
    {
        return new static($entity);
    }

    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
    }

    /**
     * Set the data to update
     * 
     * @param array $data
     * 
     * @return self
     */
    public function data(array $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Set true to handle duplications
     * 
     * @param boolean $handle
     * 
     * @return self
     */
    public function duplications($handle = true)
    {
        $this->duplications = $handle;

        return $this;
    }

    /**
     * Run the query and return all values
     * 
     * @return PDOStatement
     */
    public function run()
    {
        $marks = [];

        foreach ($this->data as $field => $value) {
            $marks[":{$field}"] = $value;
        }

        return $this->entity->getAdapter->execute((string) $this, $marks);
    }

    /**
     * Build and return the query
     * 
     * @return string
     */
    public function __toString()
    {
        if (empty($this->data)) {
            return "INSERT INTO `{$this->entity->table}` (`id`) VALUES (NULL)";
        }

        $fields = array_keys($this->data);

        $query = "INSERT INTO `{$this->entity->table}`";
        $query .= ' (`'.implode('`, `', $fields).'`)';
        $query .= ' VALUES (:'.implode(', :', $fields).')';

        if ($this->duplications) {
            $query .= ' ON DUPLICATE KEY UPDATE';
            $query .= ' id = LAST_INSERT_ID(id), '.static::buildFields($fields);
        }

        return $query;
    }

    /**
     * Generates the data part of a UPDATE query
     *
     * @param array       $fields
     *
     * @return string
     */
    protected static function buildFields(array $fields)
    {
        $query = [];

        foreach ($fields as $field) {
            $query[] = "`{$field}` = :{$field}";
        }

        return implode(', ', $query);
    }
}
