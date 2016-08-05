<?php

namespace SimpleCrud\Queries\Mysql;

use SimpleCrud\Queries\Query;
use SimpleCrud\Table;

/**
 * Manages a database update query.
 */
class Update extends Query
{
    use SelectionTrait;

    protected $data = [];

    /**
     * Set the data to update.
     *
     * @param array      $data
     * @param array|null $prepared
     *
     * @return self
     */
    public function data(array $data, array &$prepared = null)
    {
        $this->data = $this->table->prepareDataToDatabase($data, false);

        if (is_array($prepared)) {
            foreach ($this->data as $field => $value) {
                $prepared[$field] = $this->table->fields[$field]->dataFromDatabase($value);
            }
        }

        return $this;
    }

    /**
     * Adds new marks to the query.
     *
     * @param array $marks
     *
     * @return self
     */
    public function marks(array $marks)
    {
        $this->marks += $marks;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $marks = $this->marks;

        foreach ($this->data as $field => $value) {
            $marks[":__{$field}"] = $value;
        }

        return $this->table->getDatabase()->execute((string) $this, $marks);
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        $query = "UPDATE `{$this->table->name}`";
        $query .= ' SET '.static::buildFields(array_keys($this->data));

        $query .= $this->whereToString();
        $query .= $this->limitToString();

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
            $query[] = "`{$field}` = :__{$field}";
        }

        return implode(', ', $query);
    }
}
