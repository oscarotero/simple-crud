<?php
declare(strict_types = 1);

namespace SimpleCrud;

use ArrayAccess;
use Countable;
use Iterator;
use SimpleCrud\Engine\SchemeInterface;
use JsonSerializable;
use RuntimeException;

/**
 * Stores a collection of rows.
 */
class RowCollection implements ArrayAccess, Iterator, Countable, JsonSerializable
{
    private $table = [];
    private $rows = [];
    private $data = [];

    public function __construct(Table $table, Row ...$rows)
    {
        $this->table = $table;

        foreach ($rows as $row) {
            $this->rows[$row->id] = $row;
        }
    }

    public function __debugInfo(): array
    {
        return [
            'table' => $this->table->getName(),
            'rows' => $this->rows,
        ];
    }

    public function __call(string $name, array $arguments): Select
    {
        $db = $this->table->getDatabase();

        //Relations
        if (isset($db->$name)) {
            return $this->select($db->$name);
        }

        throw new BadMethodCallException(
            sprintf('Invalid method call %s', $name)
        );
    }

    /**
     * @see JsonSerializable
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Magic method to stringify the values.
     */
    public function __toString()
    {
        return json_encode($this, JSON_NUMERIC_CHECK);
    }

    /**
     * Returns the table associated with this row
     */
    public function getTable(): Table
    {
        return $this->table;
    }

    /**
     * Add extra data to the row
     */
    public function setData(string $name, $value): self
    {
        $this->data[$name] = $value;

        return $this;
    }

    /**
     * Removes a value or all extra data
     */
    public function removeData(string $name = null): self
    {
        if ($name === null) {
            $this->data = [];
        } else {
            unset($this->data[$name]);
        }

        return $this;
    }

    /**
     * Return the value of all rows
     */
    public function __get(string $name)
    {
        //It's data
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }

        //It's a table
        $db = $this->table->getDatabase();

        //Relations
        if (isset($db->$name)) {
            $result = $this->data[$name] = $this->select($db->$name)->run();
            return $result;
        }

        //It's a field
        $result = [];

        foreach ($this->rows as $id => $row) {
            $result[$id] = $row->$name;
        }

        return $result;
    }

    /**
     * Change a property of all rows
     */
    public function __set(string $name, $value)
    {
        foreach ($this->rows as $row) {
            $row->$name = $value;
        }
    }

    /**
     * Check whether a value is set or not
     */
    public function __isset($name)
    {
        return isset($this->data[$name]) || isset($this->table->{$name});
    }

    /**
     * @see ArrayAccess
     */
    public function offsetSet($offset, $value)
    {
        throw new RuntimeException('RowCollection are read-only');
    }

    /**
     * @see ArrayAccess
     */
    public function offsetExists($offset)
    {
        return isset($this->rows[$offset]);
    }

    /**
     * @see ArrayAccess
     */
    public function offsetUnset($offset)
    {
        throw new RuntimeException('RowCollection are read-only');
    }

    /**
     * @see ArrayAccess
     */
    public function offsetGet($offset)
    {
        return $this->rows[$offset] ?? null;
    }

    /**
     * @see Iterator
     */
    public function rewind()
    {
        return reset($this->rows);
    }

    /**
     * @see Iterator
     */
    public function current()
    {
        return current($this->rows);
    }

    /**
     * @see Iterator
     */
    public function key()
    {
        return key($this->rows);
    }

    /**
     * @see Iterator
     */
    public function next()
    {
        return next($this->rows);
    }

    /**
     * @see Iterator
     */
    public function valid()
    {
        return key($this->rows) !== null;
    }

    /**
     * @see Countable
     */
    public function count()
    {
        return count($this->rows);
    }

    /**
     * Returns an array with all fields of all rows
     */
    public function toArray($relations = []): array
    {
        $rows = [];

        foreach ($this->rows as $id => $row) {
            $rows[$id] = $row->toArray($relations);
        }

        return $rows;
    }

    /**
     * Save all rows in the database
     */
    public function save(): self
    {
        foreach ($this->rows as $row) {
            $row->save();
        }

        return $this;
    }

    /**
     * Delete all rows in the database
     */
    public function delete(): self
    {
        $ids = array_values($this->id);

        if (count($ids)) {
            $this->table->delete()
                ->where(field('id')->in(...$id))
                ->run();

            $this->id = null;
        }

        return $this;
    }

    /**
     * Creates a select query of a table related with this row collection
     */
    public function select(Table $table): Select
    {
        return $table->select()->relatedWith($this);
    }
}
