<?php
declare(strict_types = 1);

namespace SimpleCrud;

use ArrayAccess;
use BadMethodCallException;
use Countable;
use Iterator;
use JsonSerializable;
use RuntimeException;
use SimpleCrud\Query\Select;

/**
 * Stores a collection of rows.
 */
class RowCollection implements ArrayAccess, Iterator, Countable, JsonSerializable
{
    private $table;
    private $rows = [];
    private $relations = [];

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

        //Its a relation
        if (array_key_exists($name, $this->relations)) {
            return $this->relations[$name];
        }

        $db = $this->table->getDatabase();

        if (isset($db->$name)) {
            $result = $this->select($db->$name)->run();
            $this->linkOneToMany($result);
            return $this->relations[$name] = $result;
        }

        throw new RuntimeException(
            sprintf('Undefined property "%s" in the table "%s"', $name, $this->table)
        );
    }

    /**
     * this: posts
     * rows: comments
     */
    private function linkOneToMany(RowCollection $comments)
    {
        $table = $comments->getTable();

        //Has many (inversed of Has one)
        if ($this->table->getJoinField($table)) {
            return $comments->linkOneToMany($this);
        }

        //Has many to many
        if (!$table->getJoinField($this->table)) {
            return $this->linkManyToMany($comments);
        }

        $relations = [];
        $foreignKey = $this->table->getForeignKey();

        foreach ($comments as $comment) {
            $id = $comment->{$foreignKey};
            $comment->link($this[$id]);

            if (!isset($relations[$id])) {
                $relations[$id] = [];
            }

            $relations[$id][] = $comment;
        }

        foreach ($this as $id => $row) {
            $row->link($table->createCollection($relations[$id] ?? []));
        }
    }

    /**
     * this:posts
     * rows: categories
     */
    private function linkManyToMany(RowCollection $categories)
    {
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
     */
    public function toArray(): array
    {
        return array_map(function ($row) {
            return $row->toArray();
        }, $this->rows);
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
