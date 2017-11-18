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
                $prepared[$field] = $this->table->$field->dataFromDatabase($value);
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

        return $this->table->id->dataFromDatabase($id);
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
            return "INSERT INTO `{$this->table->getName()}` (`id`) VALUES (NULL)";
        }

        $fields = array_intersect_key($this->table->getFields(), $this->data);

        $query = "INSERT INTO `{$this->table->getName()}`";
        $query .= ' (`'.implode('`, `', array_keys($fields)).'`)';
        $query .= ' VALUES ('.self::buildFields($fields).')';

        if ($this->duplications) {
            if (!isset($this->data['id'])) {
                unset($fields['id']);
            }

            $query .= ' ON DUPLICATE KEY UPDATE';
            $query .= ' id = LAST_INSERT_ID(id), '.Update::buildFields($fields);
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

        foreach ($fields as $fieldName => $field) {
            $query[] = $field->getValueExpression(":{$fieldName}");
        }

        return implode(', ', $query);
    }
}
