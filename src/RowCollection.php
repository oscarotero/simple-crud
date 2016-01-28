<?php

namespace SimpleCrud;

use ArrayAccess;
use Iterator;
use Countable;

/**
 * Stores a collection of rows.
 */
class RowCollection extends BaseRow implements ArrayAccess, Iterator, Countable
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
        reset($this->rows);
        $first = current($this->rows);

        if (!$first) {
            return [];
        }

        //Returns related entities
        $db = $this->entity->getDb();

        if ($db->has($name)) {
            $entity = $db->get($name);

            if ($first->has($name)) {
                $collection = $entity->createCollection();

                foreach ($this->get($name) as $row) {
                    if ($row instanceof self) {
                        foreach ($row as $r) {
                            $collection[] = $r;
                        }
                    } else {
                        $collection = $row;
                    }
                }

                return $collection;
            }

            $collection = $this->selectAll($name)->get(false);

            if ($this->entity->hasOne($entity)) {
                $this->joinOne($collection);
            } else {
                $this->joinMany($collection);
            }

            return $collection;
        }

        //Returns values
        if ($first->has($name)) {
            return $this->get($name);
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
        return "\n".$this->entity->name.":\n".print_r($this->toArray(), true)."\n";
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
    public function toArray($idAsKey = false, array $parentEntities = array())
    {
        if (!empty($parentEntities) && in_array($this->entity->name, $parentEntities)) {
            return;
        }

        $rows = [];

        foreach ($this->rows as $id => $row) {
            $rows[$id] = $row->toArray($parentEntities);
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
     * Returns a slice of the content.
     *
     * @param int           $offset
     * @param int|null|true $length
     *
     * @return array
     */
    public function slice($offset = null, $length = null)
    {
        if ($length === true) {
            return current(array_slice($this->rows, $offset, 1));
        }

        return array_slice($this->rows, $offset, $length);
    }

    /**
     * Add new values to the collection.
     *
     * @param array|RowInterface $rows The new rows
     *
     * @return $this
     */
    public function add($rows)
    {
        if (is_array($rows) || ($rows instanceof self)) {
            foreach ($rows as $row) {
                $this->offsetSet(null, $row);
            }
        } elseif (isset($rows)) {
            $this->offsetSet(null, $rows);
        }

        return $this;
    }

    /**
     * Filter the rows by a value.
     *
     * @param string $name   The value name
     * @param mixed  $value  The value to filter
     * @param bool   $strict Strict mode
     *
     * @return RowCollection
     */
    public function filter($name, $value, $strict = true)
    {
        $rows = [];

        foreach ($this->rows as $row) {
            if (($row->$name === $value) || (!$strict && $row->$name == $value) || (is_array($value) && in_array($row->$name, $value, $strict))) {
                $rows[] = $row;
            }
        }

        return $this->entity->createCollection($rows);
    }

    /**
     * Find a row by a value.
     *
     * @param string $name   The value name
     * @param mixed  $value  The value to filter
     * @param bool   $strict Strict mode
     *
     * @return Row|null The rows found
     */
    public function find($name, $value, $strict = true)
    {
        foreach ($this->rows as $row) {
            if (($row->$name === $value) || (!$strict && $row->$name == $value) || (is_array($value) && in_array($row->$name, $value, $strict))) {
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
        $thisEntity = $this->entity;
        $thatEntity = $rows->getEntity();
        $thisName = $thisEntity->name;
        $thatName = $thatEntity->name;

        $foreignKey = $thisEntity->foreignKey;

        foreach ($this->rows as $row) {
            if (!isset($row->$thatName)) {
                $row->$thatName = $thatEntity->createCollection();
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
