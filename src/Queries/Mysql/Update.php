<?php

namespace SimpleCrud\Queries\Mysql;

use SimpleCrud\Queries\BaseQuery;
use SimpleCrud\Queries\WhereTrait;
use SimpleCrud\Queries\LimitTrait;
use SimpleCrud\Entity;
use PDOStatement;

/**
 * Manages a database update query.
 */
class Update extends BaseQuery
{
    use WhereTrait;
    use LimitTrait;

    protected $data = [];

    /**
     * Set the data to update.
     *
     * @param array $data
     *
     * @return self
     */
    public function data(array $data)
    {
        $this->data = $this->entity->prepareDataToDatabase($data, false);

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
     * Run the query and return all values.
     *
     * @return PDOStatement
     */
    public function run()
    {
        $marks = $this->marks;

        foreach ($this->data as $field => $value) {
            $marks[":__{$field}"] = $value;
        }

        return $this->entity->getDb()->execute((string) $this, $marks);
    }

    /**
     * Build and return the query.
     *
     * @return string
     */
    public function __toString()
    {
        $query = "UPDATE `{$this->entity->name}`";
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
