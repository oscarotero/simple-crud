<?php

namespace SimpleCrud;

/**
 * Stores the data of an table row.
 */
class Row extends AbstractRow
{
    private $values = [];
    private $relations = [];

    /**
     * {@inheritdoc}
     */
    public function __construct(Table $table)
    {
        parent::__construct($table);

        $scheme = $table->getScheme();

        foreach ($scheme as $name => $field) {
            $this->values[$name] = $field['default'];
        }
    }

    /**
     * Clear the current cache.
     */
    public function clearCache()
    {
        $this->relations = [];
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

        if (array_key_exists($name, $this->relations)) {
            return $this->relations[$name];
        }

        //Load related data
        $db = $this->getDatabase();

        if (isset($db->$name)) {
            return $this->relations[$name] = $this->__call($name, [])->run() ?: new NullValue();
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
        if (array_key_exists($name, $this->values)) {
            return $this->values[$name] = $value;
        }

        $db = $this->getDatabase();

        if (array_key_exists($name, $this->relations) || isset($db->$name)) {
            if ($value === null) {
                return $this->unrelate($db->$name);
            }

            return $this->relate($value);
        }

        throw new SimpleCrudException(sprintf('Undefined value %s', $name));
    }

    /**
     * Relate 'has-one' elements with this row.
     *
     * @param AbstractRow $row The row to relate
     */
    protected function relate(AbstractRow $row)
    {
        $table = $this->getTable();
        $related = $row->getTable();

        if (!$table->hasOne($related)) {
            throw new SimpleCrudException('Not valid relation');
        }

        if (empty($row->id)) {
            throw new SimpleCrudException('Rows without id value cannot be related');
        }

        $this->__set($related->foreignKey, $row->id);

        return $this->relations[$related->name] = $row;
    }

    /**
     * Unrelate all elements with this row.
     *
     * @param Table $related The related table
     */
    protected function unrelate(Table $related)
    {
        $table = $this->getTable();

        if (!$table->hasOne($related)) {
            throw new SimpleCrudException('Not valid unrelation');
        }

        $this->__set($related->foreignKey, null);

        return $this->relations[$related->name] = null;
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
    public function toArray($keysAsId = false, array $bannedEntities = [])
    {
        $table = $this->getTable();

        if (!empty($bannedEntities) && in_array($table->name, $bannedEntities)) {
            return;
        }

        $bannedEntities[] = $table->name;
        $data = $this->values;

        foreach ($this->relations as $name => $value) {
            $data[$name] = $value->toArray($keysAsId, $parentEntities);
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
