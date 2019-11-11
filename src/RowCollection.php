<?php
declare(strict_types = 1);

namespace SimpleCrud;

use ArrayAccess;
use BadMethodCallException;
use Countable;
use Iterator;
use JsonSerializable;
use RuntimeException;
use SimpleCrud\Queries\Select;

/**
 * Stores a collection of rows.
 */
class RowCollection implements ArrayAccess, Iterator, Countable, JsonSerializable
{
    private $table;
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
            'table' => (string) $this->table,
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

    public function setData(array $data): self
    {
        $this->data = $data + $this->data;

        return $this;
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
     * Return the value of all rows
     */
    public function __get(string $name)
    {
        //It's a field
        if (isset($this->table->{$name})) {
            $result = [];

            foreach ($this->rows as $id => $row) {
                $result[$id] = $row->$name;
            }

            return $result;
        }

        //It's a custom data
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }

        $db = $this->table->getDatabase();

        if (isset($db->$name)) {
            $table = $db->$name;
            $joinTable = $this->table->getJoinTable($table);

            //Many-to-many
            if (!$this->count()) {
                $result = $table->createCollection();

                $this->link($table, $result);
            } elseif ($joinTable) {
                $joinRows = $this->select($joinTable)->run();
                $result = $joinRows->select($table)->run();

                $this->link($table, $result, $joinRows);
            } else {
                $result = $this->select($table)->run();
                $this->link($table, $result);
            }

            return $result;
        }

        throw new RuntimeException(
            sprintf('Undefined property "%s" in the table "%s"', $name, $this->table)
        );
    }

    public function link(Table $table, RowCollection $rows, RowCollection $relations = null)
    {
        if ($relations) {
            return $this->linkThrough($rows, $relations);
        }

        //Has many (inversed of Has one)
        if ($this->table->getJoinField($table)) {
            return $rows->link($this->table, $this);
        }

        $relations = [];
        $foreignKey = $this->table->getForeignKey();

        foreach ($rows as $row) {
            $id = $row->{$foreignKey};
            $row->link($this->table, $this[$id]);

            if (!isset($relations[$id])) {
                $relations[$id] = [];
            }

            $relations[$id][] = $row;
        }

        foreach ($this as $id => $row) {
            $row->link($table, $table->createCollection($relations[$id] ?? []));
        }

        $this->data[$table->getName()] = $rows;
    }

    private function linkThrough(RowCollection $rows, RowCollection $relations)
    {
        $table = $rows->getTable();
        $relTable = $relations->getTable();
        $this_fk = $this->table->getForeignKey();
        $rows_fk = $table->getForeignKey();
        $this_in_rows = [];
        $rows_in_this = [];

        foreach ($relations as $relation) {
            $this_id = $relation->{$this_fk};
            $rows_id = $relation->{$rows_fk};

            if (empty($rows[$rows_id]) || empty($this[$this_id])) {
                continue;
            }

            if (!isset($rows_in_this[$this_id])) {
                $rows_in_this[$this_id] = [];
            }

            $rows_in_this[$this_id][] = $rows[$rows_id];

            if (!isset($this_in_rows[$rows_id])) {
                $this_in_rows[$rows_id] = [];
            }

            $this_in_rows[$rows_id][] = $this[$this_id];
        }

        foreach ($this as $id => $row) {
            $row->link($table, $table->createCollection($rows_in_this[$id] ?? []));
        }

        foreach ($rows as $id => $row) {
            $row->link($this->table, $this->table->createCollection($this_in_rows[$id] ?? []));
        }

        $this->data[$table->getName()] = $rows;

        $rows->link($relTable, $relations);
        $this->link($relTable, $relations);
    }

    /**
     * Change a property of all rows
     * @param mixed $value
     */
    public function __set(string $name, $value)
    {
        foreach ($this->rows as $row) {
            $row->$name = $value;
        }
    }

    /**
     * Check whether a value is set or not
     * @param mixed $name
     */
    public function __isset($name)
    {
        return isset($this->table->{$name});
    }

    /**
     * @see ArrayAccess
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        throw new RuntimeException('RowCollection are read-only');
    }

    /**
     * @see ArrayAccess
     * @param mixed $offset
     */
    public function offsetExists($offset)
    {
        return isset($this->rows[$offset]);
    }

    /**
     * @see ArrayAccess
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        throw new RuntimeException('RowCollection are read-only');
    }

    /**
     * @see ArrayAccess
     * @param mixed $offset
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
     * By default, the rows are not converted to arrays
     */
    public function toArray(bool $convertRows = false): array
    {
        if (!$convertRows) {
            return $this->rows;
        }

        return array_map(function ($row) {
            return $row->toArray();
        }, $this->rows);
    }

    /**
     * Apply a array_map to the rows and returns the result.
     * By default, the keys are not preserved
     */
    public function map(callable $function, bool $preserveKeys = false): array
    {
        $result = array_map($function, $this->rows);

        return $preserveKeys ? $result : array_values($result);
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
                ->where('id IN ', $ids)
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
