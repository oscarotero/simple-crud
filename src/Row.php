<?php

namespace SimpleCrud;

use SimpleCrud\Scheme\Scheme;

/**
 * Stores the data of an table row.
 */
class Row extends AbstractRow
{
    private $values = [];

    /**
     * {@inheritdoc}
     */
    public function __construct(Table $table)
    {
        parent::__construct($table);

        $scheme = $table->getScheme()['fields'];

        foreach ($scheme as $name => $field) {
            $this->values[$name] = $field['default'];
        }
    }

    /**
     * Magic method to return properties or load them automatically.
     *
     * @param string $name
     */
    public function __get($name)
    {
        //Exists?
        if (array_key_exists($name, $this->values)) {
            return $this->values[$name];
        }

        if (array_key_exists($name, $this->cache)) {
            return $this->cache[$name];
        }

        //If it's related
        if (isset($this->getTable()->getScheme()['relations'][$name])) {
            return $this->cache[$name] = $this->__call($name, [])->run() ?: new NullValue();
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
        if (array_key_exists($name, $this->values)) {
            return $this->values[$name] = $value;
        }

        if (!isset($this->getTable()->getScheme()['relations'][$name])) {
            throw new SimpleCrudException(sprintf('Undefined property "%s"', $name));
        }

        if ($value && empty($value->id)) {
            throw new SimpleCrudException('Rows without id value cannot be related');
        }

        $relation = $this->getTable()->getScheme()['relations'][$name];

        switch ($relation[0]) {
            case Scheme::HAS_ONE:
                $this->__set($relation[1], $value ? $value->id : null);
                break;

            default:
                throw new SimpleCrudException('Not supported yet');
        }

        $this->cache[$name] = $value;
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

        foreach ($this->cache as $name => $value) {
            if ($value !== null) {
                $data[$name] = $value->toArray($bannedEntities);
            }
        }

        return $data;
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
        if (empty($this->id)) {
            $this->id = $this->table->insert()
                ->data($this->values)
                ->duplications($duplications)
                ->run();
        } else {
            $this->table->update()
                ->data($this->values)
                ->byId($this->id)
                ->limit(1)
                ->run();
        }

        if ($externalRelations) {
            $this->saveExternalRelations();
        }

        $this->table->cache($this);

        return $this;
    }

    /**
     * Saves the extenal relations of this row with other row directly in the database.
     */
    protected function saveExternalRelations()
    {
        $table = $this->getTable();
        $db = $this->getDatabase();

        foreach ($this->relations as $name => $row) {
            $related = $db->$name;

            if ($table->hasOne($related)) {
                continue;
            }

            $ids = (array) $row->id;

            $bridge = $table->getBridge($related);

            if ($bridge) {
                $bridge
                    ->delete()
                    ->by($table->foreignKey, $this->id)
                    ->run();

                foreach ($ids as $id) {
                    $bridge
                        ->insert()
                        ->data([
                            $table->foreignKey => $this->id,
                            $related->foreignKey => $id,
                        ])
                        ->run();
                }

                continue;
            }

            $related
                ->update()
                ->data([
                    $table->foreignKey => $this->id,
                ])
                ->by('id', $ids)
                ->run();

            continue;
        }
    }
}
