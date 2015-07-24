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
        $this->setEntity($entity);
    }

    /**
     * Magic method to execute the get method on access to undefined property.
     *
     * @see RowCollection::get()
     */
    public function __get($name)
    {
        $method = "get$name";

        return $this->$method();
    }

    /**
     * Magic method to execute get[whatever] and load automatically related stuff or execute the same function in all rows
     *
     * @param string $fn_name      The function name
     * @param string $arguments Array with all arguments passed to the function
     *
     * @return mixed
     */
    public function __call($fn_name, $arguments)
    {
        if (strpos($fn_name, 'get') === 0) {
            $name = lcfirst(substr($fn_name, 3));
            $first = current($this->rows);

            if (!$first) {
                return [];
            }

            //Returns values
            if ($first->has($name)) {
                return $this->get($name, isset($arguments[0]) ? $arguments[0] : null);
            }

            //Returns related entities
            if (isset($this->getAdapter()->$name)) {
                $entity = $this->getAdapter()->$name;

                array_unshift($arguments, $entity);

                $result = call_user_func_array([$this, 'relationSelection'], $arguments)->all();
                $this->distribute($result);

                return $result;
            }

            //Execute getWhatever() in all rows and returns the result
            $values = [];

            foreach ($this->rows as $id => $row) {
                $values[$id] = call_user_func_array([$row, $fn_name], $arguments);
            }

            return $values;
        }

        foreach ($this->rows as $row) {
            call_user_func_array([$row, $fn_name], $arguments);
        }

        return $this;
    }

    /**
     * Magic method to print the row values (and subvalues).
     *
     * @return string
     */
    public function __toString()
    {
        return "\n".$this->getEntity()->name.":\n".print_r($this->toArray(), true)."\n";
    }

    /**
     * @see ArrayAccess
     */
    public function offsetSet($offset, $value)
    {
        if (!($value instanceof Row)) {
            throw new SimpleCrudException('Only instances of SimpleCrud\\Row must be added to collections');
        }

        if (!($offset = $value->id)) {
            throw new SimpleCrudException('Only rows with the defined id must be added to collections');
        }

        $this->rows[$offset] = $value;
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
        if (!empty($parentEntities) && in_array($this->getEntity()->name, $parentEntities)) {
            return;
        }

        $rows = [];

        foreach ($this->rows as $id => $row) {
            $rows[$id] = $row->toArray($parentEntities);
        }

        return $keysAsId ? $rows : array_values($rows);
    }

    /**
     * Returns one or all values of the collections.
     *
     * @param string $name The value name. If it's not defined returns all values
     * @param string $key  The parameter name used for the keys. If it's not defined, returns a numeric array
     *
     * @return array All values found. It generates a RowCollection if the values are rows.
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

        return $first ? null : $this->getEntity()->createCollection($rows);
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
        $thisEntity = $this->getEntity();
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
