<?php

namespace SimpleCrud;

use ArrayAccess;
use Countable;
use Iterator;
use SimpleCrud\Engine\SchemeInterface;

/**
 * Stores a collection of rows.
 */
class RowCollection extends AbstractRow implements ArrayAccess, Iterator, Countable
{
    protected $table = [];

    private $rows = [];
    private $loadedRelations = [];

    public function __construct(Table $table, array $rows)
    {
        $this->table = $table;

        foreach ($rows as $row) {
            $this[] = $row;
        }
    }

    /**
     * Debug info.
     *
     * @return array
     */
    public function __debugInfo()
    {
        return [
            'table' => $this->getTable()->getName(),
            'rows' => $this->rows,
        ];
    }

    /**
     * Magic method to get properties from all rows.
     *
     * @see self::get()
     * @param mixed $name
     */
    public function __get($name)
    {
        $table = $this->getTable();
        $scheme = $table->getScheme();

        //It's a field
        if (isset($scheme['fields'][$name])) {
            $result = [];

            foreach ($this->rows as $id => $row) {
                $result[$id] = $row->$name;
            }

            return $result;
        }

        if (!isset($scheme['relations'][$name])) {
            throw new SimpleCrudException(sprintf('Undefined property "%s"', $name));
        }

        $relation = $scheme['relations'][$name];
        $related = $this->getDatabase()->$name;
        $result = $related->createCollection();

        //It's already loaded relation
        if (in_array($name, $this->loadedRelations, true)) {
            if ($relation[0] === SchemeInterface::HAS_ONE) {
                foreach ($this->rows as $row) {
                    $result[] = $row->$name;
                }

                return $result;
            }

            foreach ($this->rows as $row) {
                foreach ($row->$name as $r) {
                    $result[] = $r;
                }
            }

            return $result;
        }

        //Load the relation
        $select = $related->select()->relatedWith($this);

        //Many to many
        if ($relation[0] === SchemeInterface::HAS_MANY_TO_MANY) {
            $statement = $select();

            foreach ($this->rows as $row) {
                $row->{$related->getName()} = $related->createCollection();
            }

            while (($data = $statement->fetch())) {
                $this->rows[$data[$relation[2]]]->{$related->getName()}[] = $result[] = $select->createRow($data);
            }

            return $result;
        }

        $rows = $select->all(true)->run();

        //Join the relations and rows
        self::join($table, $this->rows, $related, $rows, $relation);
        $this->loadedRelations[] = $name;

        foreach ($rows as $row) {
            $result[] = $row;
        }

        return $result;
    }

    /**
     * Magic method to set properties to all rows.
     *
     * @param string $name
     * @param mixed  $value
     */
    public function __set($name, $value)
    {
        $table = $this->getTable();
        $scheme = $table->getScheme();

        //It's a field
        if (isset($scheme['fields'][$name])) {
            foreach ($this->rows as $row) {
                $row->$name = $value;
            }

            return;
        }

        //It's a relation
        if (!isset($scheme['relations'][$name])) {
            throw new SimpleCrudException(sprintf('Undefined property "%s"'), $name);
        }

        $relation = $scheme['relations'][$name];

        //Check types
        if ($value === null) {
            $value = $table->createCollection();
        } elseif (!($value instanceof self)) {
            throw new SimpleCrudException(sprintf('Invalid value: %s must be a RowCollection instance or null', $name));
        }

        //Join the relations and rows
        self::join($table, $this->rows, $value->getTable(), $value, $relation);
        $this->loadedRelations[] = $name;
    }

    /**
     * Magic method to check if a property is defined or not.
     *
     * @param string $name Property name
     *
     * @return bool
     */
    public function __isset($name)
    {
        $scheme = $this->getTable()->getScheme();

        return isset($scheme['fields'][$name]) || isset($this->loadedRelations[$name]);
    }

    /**
     * @see ArrayAccess
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        if (!($value instanceof Row)) {
            throw new SimpleCrudException('Only instances of SimpleCrud\\Row must be added to collections');
        }

        if (empty($value->id)) {
            throw new SimpleCrudException('Only rows with the defined id must be added to collections');
        }

        $this->rows[$value->id] = $value;
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
        unset($this->rows[$offset]);
    }

    /**
     * @see ArrayAccess
     * @param mixed $offset
     */
    public function offsetGet($offset)
    {
        return isset($this->rows[$offset]) ? $this->rows[$offset] : null;
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
     * {@inheritdoc}
     */
    public function toArray($recursive = true, array $bannedEntities = [])
    {
        if (!$recursive) {
            return $this->rows;
        }

        $table = $this->getTable();

        if (!empty($bannedEntities) && in_array($table->getName(), $bannedEntities)) {
            return;
        }

        $rows = [];

        foreach ($this->rows as $row) {
            $rows[] = $row->toArray($recursive, $bannedEntities);
        }

        return $rows;
    }

    /**
     * Filter the rows by a value.
     *
     * @param callable $filter
     *
     * @return RowCollection
     */
    public function filter(callable $filter)
    {
        return $this->table->createCollection(array_filter($this->rows, $filter));
    }

    /**
     * Find a row by a value.
     *
     * @param callable $filter
     *
     * @return Row|null The rows found
     */
    public function find(callable $filter)
    {
        foreach ($this->rows as $row) {
            if ($filter($row) === true) {
                return $row;
            }
        }
    }

    /**
     * Join two related tables.
     *
     * @param Table               $table1
     * @param RowCollection|array $rows1
     * @param Table               $table2
     * @param RowCollection|array $rows2
     * @param array               $relation
     */
    private static function join(Table $table1, $rows1, Table $table2, $rows2, array $relation)
    {
        if ($relation[0] === SchemeInterface::HAS_ONE) {
            list($table2, $rows2, $table1, $rows1) = [$table1, $rows1, $table2, $rows2];
        }

        foreach ($rows1 as $row) {
            $row->{$table2->getName()} = $table2->createCollection();
        }

        foreach ($rows2 as $row) {
            $id = $row->{$relation[1]};

            if (isset($rows1[$id])) {
                $rows1[$id]->{$table2->getName()}[] = $row;
                $row->{$table1->getName()} = $rows1[$id];
            } else {
                $row->{$table1->getName()} = null;
            }
        }
    }
}
