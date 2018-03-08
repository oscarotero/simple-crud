<?php

namespace SimpleCrud\Queries\Mysql;

use SimpleCrud\Queries\Query;
use SimpleCrud\Table;
use SimpleCrud\Fields\Field;

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
        $this->data = array_intersect_key(
            $this->table->prepareDataToDatabase($data, false),
            $data
        );

        if (is_array($prepared)) {
            foreach ($this->data as $field => $value) {
                $prepared[$field] = $this->table->$field->dataFromDatabase($value);
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

        foreach ($this->data as $fieldName => $value) {
            $marks[":__{$fieldName}"] = $value;
        }

        return $this->table->getDatabase()->execute((string) $this, $marks);
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        $query = "UPDATE `{$this->table->getName()}`";
        $query .= ' SET '.static::buildFields(array_intersect_key($this->table->getFields(), $this->data), '__');
        $query .= $this->whereToString();
        $query .= $this->limitToString();

        return $query;
    }

    /**
     * Generates the data part of a UPDATE query.
     *
     * @param Field[] $fields
     * @param string  $prefix
     *
     * @return string
     */
    public static function buildFields(array $fields, $prefix = '')
    {
        $query = [];

        foreach ($fields as $fieldName => $field) {
            $query[] = "`{$fieldName}` = ".$field->getValueExpression(":{$prefix}{$fieldName}");
        }

        return implode(', ', $query);
    }
}
