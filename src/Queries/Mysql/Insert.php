<?php

namespace SimpleCrud\Queries\Mysql;

use SimpleCrud\Queries\Query;
use SimpleCrud\Table;

/**
 * Manages a database insert query in Mysql databases.
 */
class Insert extends Query
{
    protected $data = [];
    protected $duplications;

    /**
     * Set the data to insert.
     *
     * @param array      $data
     * @param array|null $prepared
     *
     * @return self
     */
    public function data(array $data, array &$prepared = null)
    {
        $this->data = $this->table->prepareDataToDatabase($data, true);

        if (is_array($prepared)) {
            foreach ($this->data as $field => $value) {
                $prepared[$field] = $this->table->fields[$field]->dataFromDatabase($value);
            }
        }

        return $this;
    }

    /**
     * Set true to handle duplications.
     *
     * @param bool $handle
     *
     * @return self
     */
    public function duplications($handle = true)
    {
        $this->duplications = $handle;

        return $this;
    }

    /**
     * Run the query and return the id.
     *
     * @return int
     */
    public function run()
    {
        $this->__invoke();

        $id = $this->table->getDatabase()->lastInsertId();

        return $this->table->fields['id']->dataFromDatabase($id);
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $marks = [];

        foreach ($this->data as $field => $value) {
            $marks[":{$field}"] = $value;
        }

        return $this->table->getDatabase()->execute((string) $this, $marks);
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        if (empty($this->data)) {
            return "INSERT INTO `{$this->table->name}` (`id`) VALUES (NULL)";
        }

        $fields = array_keys($this->data);

        $query = "INSERT INTO `{$this->table->name}`";
        $query .= ' (`'.implode('`, `', $fields).'`)';
        $query .= ' VALUES (:'.implode(', :', $fields).')';

        if ($this->duplications) {
            $query .= ' ON DUPLICATE KEY UPDATE';
            $query .= ' id = LAST_INSERT_ID(id), '.static::buildFields($fields);
        }

        return $query;
    }

    /**
     * Generates the data part of a UPDATE query.
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
