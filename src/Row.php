<?php

namespace SimpleCrud;

use JsonSerializable;

/**
 * Stores the data of an entity row.
 */
class Row extends BaseRow implements JsonSerializable
{
    private $values = [];

    /**
     * {@inheritdoc}
     */
    public function __construct(Entity $entity)
    {
        parent::__construct($entity);

        $this->values = array_fill_keys(array_keys($entity->fields), null);
    }

    /**
     * Magic method to return properties or load them automatically.
     *
     * @param string $name
     */
    public function __get($name)
    {
        //Return properties
        if (array_key_exists($name, $this->values)) {
            return $this->values[$name];
        }

        //Custom property
        if (isset($this->properties[$name])) {
            return $this->values[$name] = call_user_func($this->properties[$name], $this);
        }

        //Load related data
        switch ($this->entity->getRelation($name)) {
            case Entity::RELATION_HAS_ONE:
                return $this->values[$name] = $this->select($name)->get() ?: new NullValue();

            case Entity::RELATION_HAS_MANY:
            case Entity::RELATION_HAS_BRIDGE:
                return $this->values[$name] = $this->select($name)->get();
        }
    }

    /**
     * Magic method to store properties.
     *
     * @param string $name
     * @param mixed  $value
     */
    public function __set($name, $value)
    {
        $this->values[$name] = $value;
    }

    /**
     * Magic method to check if a property is defined or not.
     *
     * @param string $name Property name
     *
     * @return bool
     */
    public function __isset($name)
    {
        $value = static::__get($name);

        return isset($value) && !($value instanceof NullValue);
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
            throw new SimpleCrudException('Not valid relation');
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
     * Return whether a value is already defined or not.
     *
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        return array_key_exists($name, $this->values) && !($this->values[$name] instanceof NullValue);
    }

    /**
     * Saves this row in the database.
     *
     * @param bool $duplications      Set true to detect duplicates index
     * @param bool $externalRelations Set true to save the relations with other entities
     *
     * @return $this
     */
    public function save($duplications = false, $externalRelations = false)
    {
        $data = array_intersect_key($this->values, $this->entity->fields);

        if (empty($this->id)) {
            $this->id = $this->entity->insert()
                ->data($data)
                ->duplications($duplications)
                ->get();
        } else {
            $this->entity->update()
                ->data($data)
                ->byId($this->id)
                ->limit(1)
                ->run();
        }

        if ($externalRelations) {
            $this->saveExternalRelations();
        }

        return $this;
    }

    /**
     * Saves the extenal relations (RELATION_HAS_MANY|RELATION_HAS_BRIDGE) of this row with other row directly in the database.
     *
     * @return $this
     */
    protected function saveExternalRelations()
    {
        $extData = array_diff_key($this->values, $this->entity->fields);
        $db = $this->entity->getDb();

        foreach ($extData as $name => $ids) {
            if (!$db->has($name)) {
                continue;
            }

            $entity = $db->get($name);

            if ($this->entity->hasOne($entity)) {
                continue;
            }

            $ids = ($ids instanceof RowInterface) ? (array) $ids->id : (array) $ids;

            if ($this->entity->hasMany($entity)) {
                $entity->update()
                    ->data([
                        $this->entity->foreignKey => $this->id,
                    ])
                    ->by('id', $ids)
                    ->run();

                continue;
            }

            if ($this->entity->hasBridge($entity)) {
                $bridge = $this->entity->getBridge($entity);

                $bridge->delete()
                    ->by($this->entity->foreignKey, $this->id)
                    ->run();

                foreach ($ids as $id) {
                    $bridge->insert()
                        ->data([
                            $this->entity->foreignKey => $this->id,
                            $entity->foreignKey => $id,
                        ])
                        ->run();
                }

                continue;
            }
        }

        return $this;
    }
}
