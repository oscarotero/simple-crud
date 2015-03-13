<?php
namespace SimpleCrud;

use ArrayAccess;
use Iterator;
use Countable;
use JsonSerializable;

/**
 * SimpleCrud\RowCollection.
 *
 * Stores a row collection of an entity
 */
class RowCollection implements ArrayAccess, Iterator, Countable, JsonSerializable, RowInterface
{
    private $rows = [];

    public $entity;
    public $adapter;

    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
        $this->adapter = $entity->adapter;
    }

    /**
     * Magic method to execute the get method on access to undefined property.
     *
     * @see RowCollection::get()
     */
    public function __get($name)
    {
        return $this->get($name);
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
     * Magic method to execute get[whatever] and load automatically related stuff or execute the same function in all rows
     *
     * @param string $name      The function name
     * @param string $arguments Array with all arguments passed to the function
     *
     * @return $this
     */
    public function __call($name, $arguments)
    {
        if (strpos($name, 'get') === 0) {
            $name = lcfirst(substr($name, 3));

            if (($entity = $this->adapter->$name)) {
                array_unshift($arguments, $this);

                return call_user_func_array([$entity, 'selectBy'], $arguments);
            }
        }

        foreach ($this->rows as $row) {
            call_user_func_array([$row, $name], $arguments);
        }

        return $this;
    }

    /**
     * @see JsonSerialize
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function toArray($keysAsId = false, array $parentEntities = array())
    {
        if ($parentEntities && in_array($this->entity->name, $parentEntities)) {
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
     * @return array|RowCollection All values found. It generates a RowCollection if the values are rows.
     */
    public function get($name = null, $key = null)
    {
        if (is_int($name)) {
            if ($key === true) {
                return current(array_slice($this->rows, $name, 1));
            }

            return array_slice($this->rows, $name, $key, true);
        }

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

        if ($this->entity->isRelated($name)) {
            $entity = $this->adapter->$name;
            $collection = $entity->createCollection();

            if ($this->entity->getRelation($entity) === Entity::RELATION_HAS_ONE) {
                $collection->add($rows);
            } else {
                foreach ($rows as $rows) {
                    $collection->add($rows);
                }
            }

            return $collection;
        }

        return $rows;
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
     * Load related elements from the database.
     *
     * @param string $entity The entity name
     *
     * @return $this
     */
    public function load($entity)
    {
        if (!($entity = $this->adapter->$entity)) {
            throw new SimpleCrudException("The entity $entity does not exists");
        }

        $arguments[0] = $this;
        $result = call_user_func_array([$entity, 'selectBy'], $arguments);

        $this->distribute($result);

        return $this;
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
        if ($data instanceof Row) {
            $data = $data->entity->createCollection([$data]);
        }

        if ($data instanceof RowCollection) {
            $name = $data->entity->name;

            switch ($this->entity->getRelation($data->entity)) {
                case Entity::RELATION_HAS_MANY:
                    $foreignKey = $this->entity->foreignKey;

                    foreach ($this->rows as $row) {
                        if (!isset($row->$name)) {
                            $row->$name = $data->entity->createCollection();
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

                case Entity::RELATION_HAS_ONE:
                    $foreignKey = $data->entity->foreignKey;

                    foreach ($this->rows as $row) {
                        $row->$name = (($id = $row->$foreignKey) && isset($data[$id])) ? $data[$id] : null;
                    }

                    if ($bidirectional === true) {
                        $data->distribute($this, false);
                    }

                    return $this;
            }

            throw new SimpleCrudException("Cannot set '$name' and '{$this->entity->name}' because is not related or does not exists");
        }
    }
}
