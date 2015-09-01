<?php
namespace SimpleCrud\Queries;

use SimpleCrud\Entity;
use PDOStatement;

/**
 * Manages a database insert query in Mysql databases
 */
class Insert extends BaseQuery
{
    protected $data = [];
    protected $duplications;

    /**
     * Set the data to update
     *
     * @param array $data
     *
     * @return self
     */
    public function data(array $data)
    {
        $this->data = $this->entity->prepareDataToDatabase($data, true);

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

        return $this->entity->getDb()->execute((string) $this, $marks);
    }

    /**
     * Run the query and return the id
     *
     * @return integer
     */
    public function get()
    {
        $this->run();

        $id = $this->entity->getDb()->lastInsertId();

        return $this->entity->fields['id']->dataFromDatabase($id);
    }

    /**
     * Build and return the query
     *
     * @return string
     */
    public function __toString()
    {
        if (empty($this->data)) {
            return "INSERT INTO `{$this->entity->name}` (`id`) VALUES (NULL)";
        }

        $fields = array_keys($this->data);

        $query = "INSERT INTO `{$this->entity->name}`";
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
     * @param array $fields
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
