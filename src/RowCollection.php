<?php
namespace SimpleCrud;

use ArrayAccess;
use Iterator;
use Countable;

/**
 * Stores a collection of rows
 *
 * @property array $id
 */
class RowCollection extends BaseRow implements ArrayAccess, Iterator, Countable
{
    private $rows = [];

    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
        $this->db = $entity->getDb();
    }

    /**
     * Magic method to execute custom method defined in the entity class
     *
     * @param string $name
     */
    public function __call($name, $arguments)
    {
        $method = "rowCollection{$name}";

        if (method_exists($this->entity, $method)) {
            array_unshift($arguments, $this);

            return call_user_func_array([$this->entity, $method], $arguments);
        }
    }

    /**
     * Magic method to set properties to all rows
     * 
     * @see self::set()
     */
    public function __set($name, $value)
    {
        $this->set($name, $value);
    }

    /**
     * Magic method to get properties from all rows
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

        if (isset($db->$name)) {
            $entity = $db->$name;

            if ($first->has($name)) {
                $collection = $entity->createCollection();

                foreach ($this->get($name) as $row) {
                    if ($row instanceof RowCollection) {
                        foreach ($row as $r) {
                            $collection[] = $r;
                        }
                    } else {
                        $collection = $r;
                    }
                }

                return $collection;
            }

            $result = $this->select($name)->all();

            $this->distribute($result);

            return $result;
        }

        //Returns values
        if ($first->has($name)) {
            return $this->get($name, isset($arguments[0]) ? $arguments[0] : null);
        }
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
    public function toArray($keysAsId = false, array $parentEntities = array())
    {
        if (!empty($parentEntities) && in_array($this->entity->name, $parentEntities)) {
            return;
        }

        $rows = [];

        foreach ($this->rows as $id => $row) {
            $rows[$id] = $row->toArray($parentEntities);
        }

        return $keysAsId ? $rows : array_values($rows);
    }

    /**
     * Set values to all children
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
     * Returns a slice of the content
     *
     * @param integer           $offset
     * @param integer|null|true $length
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
        if (is_array($rows) || ($rows instanceof RowCollection)) {
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
     * @param string  $name  The value name
     * @param mixed   $value The value to filter
     * @param boolean $first Set true to return only the first row found
     *
     * @return null|RowInterface The rows found or null if no value is found and $first parameter is true
     */
    public function filter($name, $value, $first = false)
    {
        $rows = [];

        foreach ($this->rows as $row) {
            if (($row->$name === $value) || (is_array($value) && in_array($row->$name, $value, true))) {
                if ($first === true) {
                    return $row;
                }

                $rows[] = $row;
            }
        }

        return $first ? null : $this->entity->createCollection($rows);
    }

    /**
     * Distribute a row or rowcollection througth all rows.
     *
     * @param RowInterface $data          The row or rowcollection to distribute
     * @param boolean      $bidirectional Set true to distribute also in reverse direccion
     *
     * @return $this
     */
    public function distribute(RowInterface $data, $bidirectional = true)
    {
        $thisEntity = $this->entity;
        $thatEntity = $data->getEntity();

        if (!($data instanceof RowCollection)) {
            $data = $thatEntity->createCollection([$data]);
        }

        $name = $thatEntity->name;

        if ($thisEntity->hasOne($thatEntity)) {
            $foreignKey = $thatEntity->foreignKey;

            foreach ($this->rows as $row) {
                $row->$name = (($id = $row->$foreignKey) && isset($data[$id])) ? $data[$id] : null;
            }

            if ($bidirectional === true) {
                $data->distribute($this, false);
            }

            return $this;
        }

        if ($thisEntity->hasMany($thatEntity)) {
            $foreignKey = $thisEntity->foreignKey;

            foreach ($this->rows as $row) {
                if (!isset($row->$name)) {
                    $row->$name = $thatEntity->createCollection();
                }
            }

            foreach ($data as $row) {
                $id = $row->$foreignKey;

                if (isset($this->rows[$id])) {
                    $this->rows[$id]->$name->add($row);
                }
            }

            if ($bidirectional === true) {
                $data->distribute($this, false);
            }

            return $this;
        }

        throw new SimpleCrudException("Cannot set '$name' and '{$thisEntity->name}' because is not related or does not exists");
    }
}
