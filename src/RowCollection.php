<?php

namespace SimpleCrud;

use ArrayAccess;
use Iterator;
use Countable;

/**
 * Stores a collection of rows.
 */
class RowCollection extends AbstractRow implements ArrayAccess, Iterator, Countable
{
    private $rows = [];
    private $idAsKey = true;

    /**
     * Magic method to set properties to all rows.
     *
     * @see self::set()
     */
    public function __set($name, $value)
    {
        $this->set($name, $value);
    }

    /**
     * Magic method to get properties from all rows.
     *
     * @see self::get()
     */
    public function __get($name)
    {
        if (empty($this->rows)) {
            return [];
        }

        $table = $this->getTable();

        if (isset($table->fields[$name])) {
            $result = [];

            foreach ($this->rows as $id => $row) {
                $result[$id] = $row->__get($name);
            }

            return $result;
        }
    }

    /**
     * Set whether or not use the id as key.
     *
     * @param bool $idAsKey
     *
     * @return self
     */
    public function idAsKey($idAsKey)
    {
        $this->idAsKey = (boolean) $idAsKey;

        return $this;
    }

    /**
     * Magic method to print the row values (and subvalues).
     *
     * @return string
     */
    public function __toString()
    {
        return "\n".$this->table->name.":\n".print_r($this->toArray(), true)."\n";
    }

    /**
     * @see ArrayAccess
     */
    public function offsetSet($offset, $value)
    {
        if (!($value instanceof Row)) {
            throw new SimpleCrudException('Only instances of SimpleCrud\\Row must be added to collections');
        }

        if ($this->idAsKey === false) {
            $this->rows[] = $value;

            return;
        }

        if (empty($value->id)) {
            throw new SimpleCrudException('Only rows with the defined id must be added to collections');
        }

        $this->rows[$value->id] = $value;
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
        unset($this->rows[$offset]);
    }

    /**
     * @see ArrayAccess
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
    public function toArray($idAsKey = false, array $bannedEntities = array())
    {
        $table = $this->getTable();

        if (!empty($bannedEntities) && in_array($table->name, $bannedEntities)) {
            return;
        }

        $rows = [];

        foreach ($this->rows as $id => $row) {
            $rows[$id] = $row->toArray($bannedEntities);
        }

        return $idAsKey ? $rows : array_values($rows);
    }

    /**
     * Set values to all children.
     *
     * @param string $name
     * @param string $value
     *
     * @return self
     */
    public function set($name, $value)
    {
        foreach ($this->rows as $row) {
            $row->$name = $value;
        }

        return $this;
    }

    /**
     * Returns one or all values of the collections.
     *
     * @param string $name The value name. If it's not defined returns all values
     * @param string $key  The parameter name used for the keys. If it's not defined, returns a numeric array
     *
     * @return array
     */
    public function get($name = null, $key = null)
    {
        $rows = [];

        if ($name === null) {
            if ($key === null) {
                return array_values($this->rows);
            }

            foreach ($this->rows as $row) {
                $k = $row->$key;

                if (!empty($k)) {
                    $rows[$k] = $row;
                }
            }

            return $rows;
        }

        if ($key !== null) {
            foreach ($this->rows as $row) {
                $k = $row->$key;

                if (!empty($k)) {
                    $rows[$k] = $row->$name;
                }
            }

            return $rows;
        }

        foreach ($this->rows as $row) {
            $value = $row->$name;

            if (!empty($value)) {
                $rows[] = $value;
            }
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
     * Distribute a rowcollection througth all rows.
     *
     * @param RowCollection $rows
     *
     * @return $this
     */
    public function joinMany(RowCollection $rows)
    {
        $thisTable = $this->table;
        $thatTable = $rows->getTable();
        $thisName = $thisTable->name;
        $thatName = $thatTable->name;

        $foreignKey = $thisTable->foreignKey;

        foreach ($this->rows as $row) {
            if (!isset($row->$thatName)) {
                $row->$thatName = $thatTable->createCollection();
            }
        }

        foreach ($rows as $row) {
            $id = $row->$foreignKey;

            if (isset($this->rows[$id])) {
                $this->rows[$id]->$thatName->add($row);
                $row->$thisName = $this->rows[$id];
            }
        }

        return $this;
    }

    /**
     * Distribute a rowcollection througth all rows.
     * Its the opposite of $this->joinMany().
     *
     * @param RowCollection $rows
     *
     * @return $this
     */
    public function joinOne(RowCollection $rows)
    {
        $rows->joinMany($this);

        return $this;
    }
}
