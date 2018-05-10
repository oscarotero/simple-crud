<?php

namespace SimpleCrud;

use RuntimeException;
use SimpleCrud\Engine\SchemeInterface;
use function Latitude\QueryBuilder\field;

/**
 * Stores the data of an table row.
 */
class Row extends AbstractRow
{
    protected $table;

    private $values = [];
    private $relations = [];
    private $changed = false;

    public function __construct(Table $table, array $values)
    {
        $this->table = $table;
        $this->values = $table->getDefaults($values);
        $this->changed = empty($this->values['id']);
    }

    public function __debugInfo(): array
    {
        return [
            'table' => $this->table->getName(),
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
        if ($relation === SchemeInterface::HAS_ONE) {
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
            $values = $this->table->databaseValues($this->values);

            if (empty($this->id)) {
                $this->id = $this->table->insert($values)->run();
            } else {
                $this->table->update($values)
                    ->where(field('id')->eq($this->id))
                    ->run();
            }

            $this->table->cache($this);
        }

        return $this;
    }

    public function relate(Row ...$rows): self
    {
        $table1 = $this->table;

        foreach ($rows as $row) {
            $table2 = $row->getTable();

            //Has one
            if ($field = $table1->getJoinField($table2)) {
                $this->{$field->getName()} = $row->id;
                $this->save();
                continue;
            }

            //Has many
            if ($field = $table2->getJoinField($table1)) {
                $row->{$field->getName()} = $this->id;
                $row->save();
                continue;
            }

            //Has many to many
            if ($joinTable = $table1->getJoinTable($table2)) {
                $joinTable->insert([
                    $joinTable->getJoinField($table1)->getName() => $this->id,
                    $joinTable->getJoinField($table2)->getName() => $row->id,
                ])
                ->run();

                continue;
            }

            throw new RuntimeException(
                sprintf('The tables %s and %s are not related', $table1->getName(), $table2->getName())
            );
        }

        return $this;
    }

    public function unrelate(Row ...$rows): self
    {
        $table1 = $this->table;

        foreach ($rows as $row) {
            $table2 = $row->getTable();

            //Has one
            if ($field = $table1->getJoinField($table2)) {
                $this->{$field->getName()} = null;
                $this->save();
                continue;
            }

            //Has many
            if ($field = $table2->getJoinField($table1)) {
                $row->{$field->getName()} = null;
                $row->save();
                continue;
            }

            //Has many to many
            if ($joinTable = $table1->getJoinTable($table2)) {
                $joinTable->delete()
                    ->where($joinTable->getJoinField($table1)->criteria()->eq($this->id))
                    ->where($joinTable->getJoinField($table2)->criteria()->eq($row->id))
                    ->run();

                continue;
            }

            throw new RuntimeException(
                sprintf('The tables %s and %s are not related', $table1->getName(), $table2->getName())
            );
        }

        return $this;
    }

    public function unrelateAll(Table ...$tables): self
    {
        $table1 = $this->table;

        foreach ($tables as $table2) {
            //Has one
            if ($field = $table1->getJoinField($table2)) {
                $this->{$field->getName()} = null;
                $this->save();
                continue;
            }

            //Has many
            if ($field = $table2->getJoinField($table1)) {
                $table2->update([
                    $field->getName() => null,
                ])
                ->relatedWith($table1)
                ->run();
                continue;
            }

            //Has many to many
            if ($joinTable = $table1->getJoinTable($table2)) {
                $joinTable->delete()
                    ->where($joinTable->getJoinField($table1)->criteria()->eq($this->id))
                    ->where($joinTable->getJoinField($table2)->criteria()->isNotNull())
                    ->run();

                continue;
            }

            throw new RuntimeException(
                sprintf('The tables %s and %s are not related', $table1->getName(), $table2->getName())
            );
        }

        return $this;
    }
}
