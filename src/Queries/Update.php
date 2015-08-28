<?php
namespace SimpleCrud\Queries;

use SimpleCrud\Entity;
use PDOStatement;

/**
 * Manages a database update query
 */
class Update extends BaseQuery
{
    use WhereTrait;

    protected $data = [];
    protected $limit;
    protected $offset;

    /**
     * Set the data to update
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
     * Adds a LIMIT clause
     *
     * @param integer $limit
     *
     * @return self
     */
    public function limit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Adds an offset to the LIMIT clause
     *
     * @param integer $offset
     *
     * @return self
     */
    public function offset($offset)
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Adds new marks to the query
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
     * Run the query and return all values
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
     * Build and return the query
     *
     * @return string
     */
    public function __toString()
    {
        $query = "UPDATE `{$this->entity->table}`";
        $query .= ' SET '.static::buildFields(array_keys($this->data));

        $query .= $this->whereToString();

        if (!empty($this->limit)) {
            $query .= ' LIMIT';

            if (!empty($this->offset)) {
                $query .= ' '.$this->offset.',';
            }

            $query .= ' '.$this->limit;
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
            $query[] = "`{$field}` = :__{$field}";
        }

        return implode(', ', $query);
    }
}
