<?php
namespace SimpleCrud;

use SimpleCrud\Adapters\AdapterInterface;
use JsonSerializable;

/**
 * SimpleCrud\Row.
 *
 * Stores the data of an entity row
 */
class Row implements RowInterface, JsonSerializable
{
    private $values = [];
    private $changes = [];

    public $entity;
    public $adapter;

    /**
     * Row constructor.
     *
     * @param AdapterInterface $adapter
     * @param array|null       $data
     */
    public function __construct(AdapterInterface $adapter, array $data = null)
    {
        $this->entity = $entity;
        $this->adapter = $entity->adapter;

        if ($data) {
            $this->values = $data;
        }

        $this->values += $entity->getDefaults();
    }

    /**
     * Magic method to execute 'get' functions and save the result in a property.
     *
     * @param string $name The property name
     */
    public function __get($name)
    {
        $method = "get$name";

        if (array_key_exists($name, $this->values)) {
            return $this->values[$name];
        }

        if (method_exists($this, $method)) {
            return $this->values[$name] = $this->$method();
        }

        if ($this->entity->isRelated($name)) {
            $fn = "get$name";

            return $this->values[$name] = $this->$fn();
        }
    }

    /**
     * Magic method to execute 'set' function.
     *
     * @param string $name  The property name
     * @param mixed  $value The value
     */
    public function __set($name, $value)
    {
        $this->changes[$name] = true;
        $this->values[$name] = $value;
    }

    /**
     * Magic method to check if a property is defined or is empty.
     *
     * @param string $name Property name
     *
     * @return boolean
     */
    public function __isset($name)
    {
        return !empty($this->values[$name]);
    }

    /**
     * Magic method to execute get[whatever] and load automatically related stuff.
     *
     * @param string $name
     * @param string $arguments
     *
     * @throws SimpleCrudException
     */
    public function __call($name, $arguments)
    {
        if ((strpos($name, 'get') === 0) && ($name = lcfirst(substr($name, 3)))) {
            if (!$arguments && array_key_exists($name, $this->values)) {
                return $this->values[$name];
            }

            if (($entity = $this->adapter->$name)) {
                array_unshift($arguments, $this);

                return call_user_func_array([$entity, 'selectBy'], $arguments);
            }
        }

        throw new SimpleCrudException("The method $name does not exists");
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
     * jsonSerialize interface.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Return if the row values has been changed or not.
     *
     * @return boolean
     */
    public function changed()
    {
        return !empty($this->changes);
    }

    /**
     * Reload the row from the database.
     *
     * @throws SimpleCrudException
     *
     * @return $this
     */
    public function reload()
    {
        if (!$this->id || !($row = $this->entity->selectBy($this->id))) {
            throw new SimpleCrudException("This row does not exist in database");
        }

        $this->changes = [];
        $this->values = $row->get();

        return $this;
    }

    /**
     * Relate 'has-one' elements with this row.
     *
     * @param RowInterface $row The row to relate
     *
     * @return $this
     */
    public function setRelation(RowInterface $row)
    {
        if (func_num_args() > 1) {
            foreach (func_get_args() as $row) {
                $this->setRelation($row);
            }

            return $this;
        }

        if ($this->entity->getRelation($row->entity) !== Entity::RELATION_HAS_ONE) {
            throw new SimpleCrudException("Not valid relation");
        }

        if (empty($row->id)) {
            throw new SimpleCrudException('Rows without id value cannot be related');
        }

        $this->{$row->entity->foreignKey} = $row->id;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray($keysAsId = false, array $parentEntities = array())
    {
        if ($parentEntities && in_array($this->entity->name, $parentEntities)) {
            return;
        }

        $parentEntities[] = $this->entity->name;
        $data = $this->values;

        foreach ($data as &$value) {
            if ($value instanceof RowInterface) {
                $value = $value->toArray($keysAsId, $parentEntities);
            }
        }

        return $data;
    }

    /**
     * Set new values to the row.
     *
     * @param array   $data               The new values
     * @param boolean $onlyDeclaredFields Set true to only set declared fields
     *
     * @return $this
     */
    public function set(array $data, $onlyDeclaredFields = false)
    {
        if ($onlyDeclaredFields === true) {
            $data = array_intersect_key($data, $this->entity->getFieldsNames());
        }

        foreach ($data as $name => $value) {
            $this->changes[$name] = true;
            $this->values[$name] = $value;
        }

        return $this;
    }

    /**
     * Return one or all values of the row.
     *
     * @param true|null|string $name The value name to recover. If it's not defined, returns all values. If it's true, returns only the fields values.
     *
     * @return mixed
     */
    public function get($name = null, $onlyChangedValues = false)
    {
        $values = ($onlyChangedValues === true) ? array_intersect_key($this->values, $this->changes) : $this->values;

        if ($name === true) {
            return array_intersect_key($values, $this->entity->getFieldsNames());
        }

        if ($name === null) {
            return $values;
        }

        return isset($values[$name]) ? $values[$name] : null;
    }

    /**
     * Saves this row in the database.
     *
     * @param boolean $duplicateKey      Set true to detect duplicates index
     * @param boolean $onlyChangedValues Set false to save all values instead only the changed (only for updates)
     *
     * @return $this
     */
    public function save($duplicateKey = false, $onlyChangedValues = true)
    {
        $data = $this->get(true);

        if (empty($this->id)) {
            $data = $this->entity->insert($data, $duplicateKey);
        } else {
            $data = $this->entity->update($data, 'id = :id', [':id' => $this->id], 1, ($onlyChangedValues ? $this->changes : null));
        }

        $this->set($data);
        $this->changes = [];

        return $this;
    }

    /**
     * Deletes the row in the database.
     *
     * @return $this
     */
    public function delete()
    {
        if (empty($this->id)) {
            return false;
        }

        $this->entity->delete('id = :id', [':id' => $this->id], 1);

        return $this;
    }
}
