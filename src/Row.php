<?php

namespace SimpleCrud;

use SimpleCrud\Scheme\Scheme;

/**
 * Stores the data of an table row.
 */
class Row extends AbstractRow
{
    private $values = [];
    private $relations = [];
    private $changed = false;

    /**
     * {@inheritdoc}
     */
    public function __construct(Table $table)
    {
        parent::__construct($table);

        $defaults = [];

        foreach ($table->getScheme()['fields'] as $name => $field) {
            $defaults[$name] = $field['default'];
        }

        $this->init($defaults);
    }

    /**
     * Clear the current cache.
     */
    public function clearCache()
    {
        $this->relations = [];
    }

    /**
     * Initialize the row with the data from database.
     * 
     * @param array $values
     * @param array $relations
     */
    public function init(array $values, array $relations = [])
    {
        $this->values = $values;
        $this->relations = $relations;
        $this->changed = false;
    }

    /**
     * Magic method to return properties or load them automatically.
     *
     * @param string $name
     */
    public function __get($name)
    {
        //It's a field
        if (array_key_exists($name, $this->values)) {
            return $this->values[$name];
        }

        //It's a relation
        if (array_key_exists($name, $this->relations)) {
            return $this->relations[$name] ?: new NullValue();
        }

        //Load the relation
        if (isset($this->getTable()->getScheme()['relations'][$name])) {
            return ($this->relations[$name] = call_user_func([$this, $name])->run()) ?: new NullValue();
        }

        throw new SimpleCrudException(sprintf('Undefined property "%s"', $name));
    }

    /**
     * Magic method to store properties.
     *
     * @param string $name
     * @param mixed  $value
     */
    public function __set($name, $value)
    {
        //It's a field
        if (array_key_exists($name, $this->values)) {
            if ($this->values[$name] !== $value) {
                $this->changed = true;
            }

            return $this->values[$name] = $value;
        }

        //It's a relation
        $table = $this->getTable();

        if (!isset($table->getScheme()['relations'][$name])) {
            throw new SimpleCrudException(sprintf('Undefined property "%s"', $name));
        }

        $relation = $table->getScheme()['relations'][$name][0];

        //Check types
        if ($relation === Scheme::HAS_ONE) {
            if ($value !== null && !($value instanceof self)) {
                throw new SimpleCrudException(sprintf('Invalid value: %s must be a Row instance or null', $name));
            }
        } elseif (!($value instanceof RowCollection)) {
            throw new SimpleCrudException(sprintf('Invalid value: %s must be a RowCollection', $name));
        }

        $this->relations[$name] = $value;
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
        return isset($this->values[$name]) || isset($this->relations[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(array $bannedEntities = [])
    {
        $table = $this->getTable();

        if (!empty($bannedEntities) && in_array($table->name, $bannedEntities)) {
            return;
        }

        $bannedEntities[] = $table->name;
        $data = $this->values;

        foreach ($this->relations as $name => $value) {
            if ($value !== null) {
                $data[$name] = $value->toArray($bannedEntities);
            }
        }

        return $data;
    }

    /**
     * Saves this row in the database.
     *
     * @param bool|array $relations Set true to save the relations with other entities
     *
     * @return $this
     */
    public function save($relations = false)
    {
        if ($relations === true) {
            $relations = array_keys($this->relations);
        }

        if ($relations) {
            $scheme = $this->getTable()->getScheme()['relations'];

            foreach ($relations as $name) {
                if (!array_key_exists($name, $this->relations)) {
                    continue;
                }

                if (!isset($scheme[$name])) {
                    throw new SimpleCrudException(sprintf('Invalid relation: %s', $name));
                }

                $relation = $scheme[$name];

                if ($relation[0] === Scheme::HAS_ONE) {
                    $this->{$relation[1]} = ($this->relations[$name] === null) ? null : $this->relations[$name]->save()->id;
                }
            }
        }

        if (!$this->changed) {
            return $this;
        }

        if (empty($this->id)) {
            $this->id = $this->table->insert()
                ->data($this->values)
                ->run();
        } else {
            $this->table->update()
                ->data($this->values)
                ->byId($this->id)
                ->limit(1)
                ->run();
        }

        if ($relations) {
            $scheme = $this->getTable()->getScheme()['relations'];

            foreach ($relations as $name) {
                if (!array_key_exists($name, $this->relations)) {
                    continue;
                }

                if (!isset($scheme[$name])) {
                    throw new SimpleCrudException(sprintf('Invalid relation: %s', $name));
                }

                $relation = $scheme[$name];

                if ($relation[0] === Scheme::HAS_MANY) {
                    foreach ($this->relations[$name] as $row) {
                        $row->{$relation[1]} = $this->id;
                        $row->save();
                    }

                    continue;
                }

                if ($relation[0] === Scheme::HAS_MANY_TO_MANY) {
                    $bridge = $this->getDatabase()->{$relation[1]};

                    $bridge
                        ->delete()
                        ->by($relation[2], $this->id)
                        ->run();

                    foreach ($this->relations[$name] as $row) {
                        $bridge
                            ->insert()
                            ->data([
                                $relation[2] => $this->id,
                                $relation[3] => $row->id,
                            ])
                            ->run();
                    }
                }
            }
        }

        $this->table->cache($this);

        return $this;
    }
}
