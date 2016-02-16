<?php

namespace SimpleCrud;

use JsonSerializable;
use SimpleCrud\Scheme\Scheme;

/**
 * Base class used by Row and RowCollection.
 *
 * @property mixed $id
 */
abstract class AbstractRow implements JsonSerializable
{
    protected $table;

    /**
     * Constructor.
     *
     * @param Table $table
     */
    public function __construct(Table $table)
    {
        $this->table = $table;
    }

    /**
     * Returns the table associated with this row.
     *
     * @return Table
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Returns the database associated with this row.
     *
     * @return SimpleCrud
     */
    public function getDatabase()
    {
        return $this->getTable()->getDatabase();
    }

    /**
     * @see JsonSerializable
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Magic method to stringify the values.
     *
     * @return string
     */
    public function __toString()
    {
        return json_encode($this, JSON_NUMERIC_CHECK);
    }

    /**
     * Converts this object into an array.
     * 
     * @param array $bannedEntities
     *
     * @return array
     */
    abstract public function toArray(array $bannedEntities = []);

    /**
     * Magic method to return properties.
     * 
     * @param string $name
     *
     * @return mixed
     */
    abstract public function __get($name);

    /**
     * Magic method to edit a property.
     * 
     * @param string $name
     * @param mixed  $value
     *
     * @return mixed
     */
    abstract public function __set($name, $value);

    /**
     * Deletes the row(s) in the database.
     *
     * @return self
     */
    public function delete()
    {
        $id = $this->id;

        if (!empty($id)) {
            $this->getTable()->delete()
                ->byId($id)
                ->run();

            $this->id = null;
        }

        return $this;
    }

    /**
     * Magic method to execute queries.
     *
     * @param string $name
     */
    public function __call($name, $arguments)
    {
        if (!isset($this->getTable()->getScheme()['relations'][$name])) {
            throw new \BadMethodCallException(sprintf('Call to undefined method %s', $name));
        }

        $table = $this->getTable();
        $relation = $table->getScheme()['relations'][$name];
        $related = $table->getDatabase()->$name;

        switch ($relation[0]) {
            case Scheme::HAS_ONE:
                return $related->select()->one()->relatedWith($this);

            default:
                return $related->select()->relatedWith($this);
        }
    }
}
