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

        foreach ($table->getScheme()['fields'] as $name => $field) {
            $this->values[$name] = null;
        }
    }

    /**
     * Debug info.
     * 
     * @return array
     */
    public function __debugInfo()
    {
        return [
            'table' => $this->getTable()->getName(),
            'values' => $this->values,
        ];
    }

    /**
     * Return the current cache.
     * 
     * @return array
     */
    public function getCache()
    {
        return $this->relations;
    }

    /**
     * Set a new cache.
     * 
     * @param array $relations
     */
    public function setCache(array $relations)
    {
        return $this->relations = $relations;
    }

    /**
     * Magic method to return properties or load them automatically.
     *
     * @param string $name
     */
    public function &__get($name)
    {
        //It's a field
        if (array_key_exists($name, $this->values)) {
            return $this->values[$name];
        }

        //It's a relation
        if (array_key_exists($name, $this->relations)) {
            $return = $this->relations[$name] ?: new NullValue();
            return $return;
        }

        //It's a localizable field
        $language = $this->getDatabase()->getAttribute(SimpleCrud::ATTR_LOCALE);

        if (!is_null($language)) {
            $localeName = "{$name}_{$language}";

            if (array_key_exists($localeName, $this->values)) {
                return $this->values[$localeName];
            }
        }

        //Load the relation
        $scheme = $this->getTable()->getScheme();

        if (isset($scheme['relations'][$name])) {
            $return = call_user_func([$this, $name])->run() ?: new NullValue();
            $this->relations[$name] = $return;
            return $return;
        }

        //Exists as a function
        if (method_exists($this, $name)) {
            $return = $this->$name();
            return $return;
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

        //It's a localizable field
        $language = $this->getDatabase()->getAttribute(SimpleCrud::ATTR_LOCALE);

        if (!is_null($language)) {
            $localeName = "{$name}_{$language}";

            if (array_key_exists($localeName, $this->values)) {
                if ($this->values[$localeName] !== $value) {
                    $this->changed = true;
                }

                return $this->values[$localeName] = $value;
            }
        }

        //It's a relation
        $table = $this->getTable();
        $scheme = $table->getScheme();

        if (!isset($scheme['relations'][$name])) {
            throw new SimpleCrudException(sprintf('Undefined property "%s"', $name));
        }

        $relation = $scheme['relations'][$name][0];

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
        $language = $this->getDatabase()->getAttribute(SimpleCrud::ATTR_LOCALE);

        if (!is_null($language) && isset($this->values["{$name}_{$language}"])) {
            return true;
        }

        return isset($this->values[$name]) || isset($this->relations[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray($recursive = true, array $bannedEntities = [])
    {
        if (!$recursive) {
            return $this->values;
        }

        $table = $this->getTable();

        if (!empty($bannedEntities) && in_array($table->getName(), $bannedEntities)) {
            return;
        }

        $bannedEntities[] = $table->getName();
        $data = $this->values;

        foreach ($this->relations as $name => $value) {
            if ($value !== null) {
                $data[$name] = $value->toArray(true, $bannedEntities);
            }
        }

        return $data;
    }

    public function edit(array $values)
    {
        foreach ($values as $name => $value) {
            $this->__set($name, $value);
        }

        return $this;
    }

    /**
     * Saves this row in the database.
     *
     * @return $this
     */
    public function save()
    {
        if ($this->changed) {
            if (empty($this->id)) {
                $this->id = $this->table->insert()
                    ->data($this->values, $this->values)
                    ->run();
            } else {
                $this->table->update()
                    ->data($this->values, $this->values)
                    ->byId($this->id)
                    ->limit(1)
                    ->run();
            }

            $this->table->cache($this);
        }

        return $this;
    }

    /**
     * Relate this row with other row and save the relation.
     *
     * @param Row ...$row
     *
     * @return $this
     */
    public function relate(Row $row)
    {
        $table = $this->getTable();
        $relations = $table->getScheme()['relations'];
        $rows = [];

        foreach (func_get_args() as $row) {
            $relationTable = $row->getTable();

            if (!isset($relations[$relationTable->getName()])) {
                throw new SimpleCrudException(sprintf('Invalid relation: %s - %s', $table->getName(), $relationTable->getName()));
            }

            $relation = $relations[$relationTable->getName()];

            if (!isset($relations[$relation[0]])) {
                $relations[$relation[0]] = [];
            }

            $relations[$relation[0]][] = [
                $relation,
                $relationTable,
                $row
            ];
        }

        if (isset($relations[Scheme::HAS_ONE])) {
            foreach ($relations[Scheme::HAS_ONE] as $r) {
                list($relation, $relationTable, $row) = $r;

                if ($row->id === null) {
                    $row->save();
                }

                $this->{$relation[1]} = $row->id;
                $this->relations[$relationTable->getName()] = $row;
            }

            $this->save();

            foreach ($relations[Scheme::HAS_ONE] as $r) {
                list($relation, $relationTable, $row) = $r;

                if ($table->getName() !== $relationTable->getName()) {
                    $cache = $row->getCache();

                    if (isset($cache[$table->getName()])) {
                        $cache[$table->getName()][] = $this;
                        $row->setCache($cache);
                    }
                }
            }
        }

        if (isset($relations[Scheme::HAS_MANY])) {
            if ($this->id === null) {
                $this->save();
            }

            foreach ($relations[Scheme::HAS_MANY] as $r) {
                list($relation, $relationTable, $row) = $r;

                $row->{$relation[1]} = $this->id;
                $row->save();

                if (isset($this->relations[$relationTable->getName()])) {
                    $this->relations[$relationTable->getName()][] = $row;
                }

                if ($table->getName() !== $relationTable->getName()) {
                    $cache = $row->getCache();
                    $cache[$table->getName()] = $this;
                    $row->setCache($cache);
                }
            }
        }

        if (isset($relations[Scheme::HAS_MANY_TO_MANY])) {
            if ($this->id === null) {
                $this->save();
            }

            foreach ($relations[Scheme::HAS_MANY_TO_MANY] as $r) {
                list($relation, $relationTable, $row) = $r;

                $bridge = $this->getDatabase()->{$relation[1]};

                if ($row->id === null) {
                    $row->save();
                }

                $bridge
                    ->insert()
                    ->duplications()
                    ->data([
                        $relation[2] => $this->id,
                        $relation[3] => $row->id,
                    ])
                    ->run();

                if (isset($this->relations[$relationTable->getName()])) {
                    $this->relations[$relationTable->getName()][] = $row;
                }
            }
        }

        return $this;
    }

    /**
     * Unrelate this row with other row and save it.
     *
     * @param Row $row
     *
     * @return $this
     */
    public function unrelate(Row $row)
    {
        $table = $this->getTable();
        $relationTable = $row->getTable();
        $relations = $table->getScheme()['relations'];

        if (!isset($relations[$relationTable->getName()])) {
            throw new SimpleCrudException(sprintf('Invalid relation: %s - %s', $table->getName(), $relationTable->getName()));
        }

        $relation = $relations[$relationTable->getName()];

        if ($relation[0] === Scheme::HAS_ONE) {
            $row->unrelate($this);

            return $this;
        }

        if ($relation[0] === Scheme::HAS_MANY) {
            if ($row->{$relation[1]} === $this->id) {
                $row->{$relation[1]} = null;
                $row->save();
            }

            if (isset($this->relations[$relationTable->getName()])) {
                unset($this->relations[$relationTable->getName()][$row->id]);
            }

            if ($table->getName() !== $relationTable->getName()) {
                $cache = $row->getCache();
                $cache[$table->getName()] = new NullValue();
                $row->setCache($cache);
            }

            return $this;
        }

        if ($relation[0] === Scheme::HAS_MANY_TO_MANY) {
            $bridge = $this->getDatabase()->{$relation[1]};

            $bridge
                ->delete()
                ->by($relation[2], $this->id)
                ->by($relation[3], $row->id)
                ->run();

            unset($this->relations[$relation[1]]);
            unset($this->relations[$relationTable->getName()][$row->id]);

            $cache = $row->getCache();
            unset($cache[$relation[1]]);
            unset($cache[$table->getName()][$this->id]);
            $row->setCache($cache);
        }
    }

    /**
     * Unrelate this row with all rows of a specific table.
     *
     * @param Table $relationTable
     *
     * @return $this
     */
    public function unrelateAll(Table $relationTable)
    {
        $table = $this->getTable();
        $relations = $table->getScheme()['relations'];

        if (!isset($relations[$relationTable->getName()])) {
            throw new SimpleCrudException(sprintf('Invalid relation: %s - %s', $table->getName(), $relationTable->getName()));
        }

        $relation = $relations[$relationTable->getName()];

        if ($relation[0] === Scheme::HAS_ONE) {
            $this->{$relation[1]} = null;
            $this->relations[$relationTable->getName()] = new NullValue();

            return $this->save();
        }

        if ($relation[0] === Scheme::HAS_MANY) {
            $relationTable->update()
                ->data([
                    $relation[1] => null,
                ])
                ->by($relation[1], $this->id)
                ->run();

            $this->relations[$relationTable->getName()] = $relationTable->createCollection();

            return $this;
        }

        if ($relation[0] === Scheme::HAS_MANY_TO_MANY) {
            $bridge = $this->getDatabase()->{$relation[1]};

            $bridge
                ->delete()
                ->by($relation[2], $this->id)
                ->run();

            $this->relations[$bridge->getName()] = $bridge->createCollection();
            $this->relations[$relationTable->getName()] = $relationTable->createCollection();
        }
    }
}
