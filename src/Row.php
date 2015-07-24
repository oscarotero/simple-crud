<?php
namespace SimpleCrud;

use JsonSerializable;

/**
 * Stores the data of an entity row
 *
 * @property mixed $id
 */
class Row extends BaseRow implements JsonSerializable
{
    private $values = [];

    /**
     * Row constructor.
     *
     * @param Entity     $entity
     * @param array|null $data
     */
    public function __construct(Entity $entity, array $data = null)
    {
        $this->setEntity($entity);

        if (!empty($data)) {
            $this->values = $data;
        }

        $this->values += $entity->getDefaults();
    }

    /**
     * Magic method to return properties or load them automatically
     *
     * @param string $name
     */
    public function __get($name)
    {
        if (array_key_exists($name, $this->values)) {
            return $this->values[$name];
        }

        //execute getName()
        $method = "get$name";

        if (method_exists($this, $method) || $this->getEntity()->isRelated($name)) {
            return $this->values[$name] = $this->$method();
        }
    }

    /**
     * Magic method to store properties
     *
     * @param string $name
     * @param mixed  $value
     */
    public function __set($name, $value)
    {
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
     * Magic method to execute get[whatever] and set[whatever] and load/save automatically related stuff.
     *
     * @param string $fn_name
     * @param string $arguments
     *
     * @throws SimpleCrudException
     */
    public function __call($fn_name, $arguments)
    {
        if (strpos($fn_name, 'get') === 0) {
            $name = lcfirst(substr($fn_name, 3));

            //Returns values
            if (!$arguments && array_key_exists($name, $this->values)) {
                return $this->values[$name];
            }

            //Returns related entities
            if (isset($this->getAdapter()->$name)) {
                $entity = $this->getAdapter()->$name;

                array_unshift($arguments, $entity);

                $select = call_user_func_array([$this, 'relationSelection'], $arguments);
                
                if ($entity->hasOne($this)) {
                    return $select->one();
                }

                return $select->all();
            }
        }

        if (strpos($fn_name, 'set') === 0) {
            $name = lcfirst(substr($fn_name, 3));

            //Save values
            if (!$arguments && array_key_exists($name, $this->values)) {
                $this->values[$name] = isset($arguments[0]) ? $arguments[0] : null;

                return $this;
            }
        }

        throw new SimpleCrudException("The method $fn_name does not exists");
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
     * @see RowInterface
     *
     * {@inheritdoc}
     */
    public function toArray($keysAsId = false, array $parentEntities = array())
    {
        if (!empty($parentEntities) && in_array($this->getEntity()->name, $parentEntities)) {
            return;
        }

        $parentEntities[] = $this->getEntity()->name;
        $data = $this->values;

        foreach ($data as &$value) {
            if ($value instanceof RowInterface) {
                $value = $value->toArray($keysAsId, $parentEntities);
            }
        }

        return $data;
    }

    /**
     * Relate 'has-one' elements with this row.
     *
     * @param RowInterface $row The row to relate
     *
     * @return $this
     */
    public function relateWith(RowInterface $row)
    {
        if (!$this->getEntity()->hasOne($row->getEntity())) {
            throw new SimpleCrudException("Not valid relation");
        }

        if (empty($row->id)) {
            throw new SimpleCrudException('Rows without id value cannot be related');
        }

        $this->{$row->getEntity()->foreignKey} = $row->id;

        return $this;
    }

    /**
     * Set new values to the row.
     *
     * @param array   $data               The new values
     *
     * @return $this
     */
    public function set(array $data)
    {
        $this->values = $data + $this->values;

        return $this;
    }

    /**
     * Return one or all values of the row.
     *
     * @param null|string $name The value name to recover. If it's not defined, returns all values.
     *
     * @return mixed
     */
    public function get($name = null)
    {
        if ($name === null) {
            return $this->values;
        }

        return isset($this->values[$name]) ? $this->values[$name] : null;
    }

    /**
     * Return whether a value is defined or not
     *
     * @param string $name
     *
     * @return boolean
     */
    public function has($name)
    {
        return array_key_exists($name, $this->values);
    }

    /**
     * Saves this row in the database.
     *
     * @param boolean $duplicate Set true to detect duplicates index
     *
     * @return $this
     */
    public function save($duplicate = false)
    {
        $data = array_intersect_key($this->values, $this->getEntity()->fields);

        if (empty($this->id)) {
            $this->getEntity->insert($data, $duplicate);
            $this->id = $this->getAdapter()->lastInsertId();

            return $this;
        }

        $this->getEntity()->update($data, 'id = :id', [':id' => $this->id], 1);

        return $this;
    }

    /**
     * Deletes the row in the database.
     *
     * @return $this
     */
    public function delete()
    {
        if (!empty($this->id)) {
            $this->getEntity()->delete('id = :id', [':id' => $this->id], 1);
            $this->id = null;
        }

        return $this;
    }
}
