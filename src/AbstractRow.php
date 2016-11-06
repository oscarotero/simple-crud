<?php

namespace SimpleCrud;

use JsonSerializable;
use SimpleCrud\Scheme\Scheme;
use Closure;

/**
 * Base class used by Row and RowCollection.
 *
 * @property mixed $id
 */
abstract class AbstractRow implements JsonSerializable
{
    protected $table;
    private $methods = [];
    private $bindMethods = [];

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
     * @param bool  $recursive
     * @param array $bannedEntities
     *
     * @return array
     */
    abstract public function toArray($recursive = true, array $bannedEntities = []);

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
     * Register a custom method.
     *
     * @param string  $name
     * @param Closure $method
     *
     * @return self
     */
    public function setMethod($name, Closure $method)
    {
        $this->methods[$name] = $method;
        unset($this->bindMethods[$name]);

        return $this;
    }

    /**
     * Magic method to execute queries or custom methods.
     *
     * @param string $name
     */
    public function __call($name, $arguments)
    {
        if (isset($this->methods[$name])) {
            if (!isset($this->bindMethods[$name])) {
                $this->bindMethods[$name] = $this->methods[$name]->bindTo($this);
            }

            return call_user_func_array($this->bindMethods[$name], $arguments);
        }

        $scheme = $this->getTable()->getScheme();

        if (!isset($scheme['relations'][$name])) {
            throw new \BadMethodCallException(sprintf('Call to undefined method %s', $name));
        }

        $table = $this->getTable();
        $relation = $table->getScheme()['relations'][$name];
        $related = $table->getDatabase()->$name;

        if ($relation[0] === Scheme::HAS_ONE) {
            return $related->select()->one()->relatedWith($this);
        }

        return $related->select()->relatedWith($this);
    }
}
