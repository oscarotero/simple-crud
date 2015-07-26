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
    private $values;

    /**
     * Row constructor.
     *
     * @param Entity $entity
     */
    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
        $this->db = $entity->getDb();
        $this->values = $entity->defaults;
    }

    /**
     * Magic method to execute custom method defined in the entity class
     *
     * @param string $name
     */
    public function __call($name, $arguments)
    {
        $method = "row{$name}";

        if (method_exists($this->entity, $method)) {
            array_unshift($arguments, $this);

            return call_user_func_array([$this->entity, $method], $arguments);
        }
    }

    /**
     * Magic method to return properties or load them automatically
     *
     * @param string $name
     */
    public function __get($name)
    {
        //Return properties
        if (array_key_exists($name, $this->values)) {
            return $this->values[$name];
        }

        //Custom method
        if (method_exists($this->entity, "row{$name}")) {
            return $this->values[$name] = $this->__call($name, []);
        }

        //Load related data
        if ($this->entity->hasOne($name)) {
            return $this->values[$name] = $this->select($name)->one();
        }

        if ($this->entity->hasMany($name)) {
            return $this->values[$name] = $this->select($name)->all();
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
        if (!empty($parentEntities) && in_array($this->entity->name, $parentEntities)) {
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
     * Relate 'has-one' elements with this row.
     *
     * @param RowInterface $row The row to relate
     *
     * @return $this
     */
    public function relateWith(RowInterface $row)
    {
        if (!$this->entity->hasOne($row->getEntity())) {
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
     * @param array $data The new values
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
     * @param boolean $duplications Set true to detect duplicates index
     *
     * @return $this
     */
    public function save($duplications = false)
    {
        $data = array_intersect_key($this->values, $this->entity->fields);

        if (empty($this->id)) {
            $this->id = $this->db->insert($this->entity->name)
                ->data($data)
                ->duplications($duplications)
                ->get();

            return $this;
        }

        $this->db->update($this->entity->name)
            ->data($data)
            ->byId($this->id)
            ->limit(1)
            ->run();

        return $this;
    }
}
