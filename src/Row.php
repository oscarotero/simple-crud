<?php

namespace SimpleCrud;

use RuntimeException;
use BadMethodCallException;
use SimpleCrud\Engine\SchemeInterface;
use function Latitude\QueryBuilder\field;
use SimpleCrud\Engine\Common\Query\Select;

/**
 * Stores the data of an table row.
 */
class Row extends AbstractRow
{
    protected $table;

    private $values = [];
    private $data = [];
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

    public function __call(string $name, array $arguments): Select
    {
        $db = $this->table->getDatabase();

        //Relations
        if (isset($db->$name)) {
            return $this->select($db->$name);
        }

        throw new BadMethodCallException(
            sprintf('Invalid method call %s', $name)
        );
        
    }

    public function getData(string $name = null)
    {
        if ($name === null) {
            return $this->data;
        }

        return $this->data[$name] ?? null;
    }

    public function setData(string $name, $value): self
    {
        $this->data[$name] = $value;

        return $this;
    }

    public function removeData(string $name = null): self
    {
        if ($name === null) {
            $this->data = [];
        } else {
            unset($this->data[$name]);
        }

        return $this;
    }

    public function &__get(string $name)
    {
        //It's a field
        if (array_key_exists($name, $this->values)) {
            return $this->values[$name];
        }

        //It's data
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }

        //It's a localizable field
        $language = $this->getDatabase()->getAttribute(SimpleCrud::ATTR_LOCALE);

        if (!is_null($language)) {
            $localeName = "{$name}_{$language}";

            if (array_key_exists($localeName, $this->values)) {
                return $this->values[$localeName];
            }
        }

        //It's a table
        $db = $this->table->getDatabase();

        //Relations
        if (isset($db->$name)) {
            $result = $this->data[$name] = $this->select($db->$name)->run();
            return $result;
        }

        throw new RuntimeException(
            sprintf('Undefined property "%s"', $name)
        );
    }

    public function __set(string $name, $value)
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
    }

    public function __isset(string $name): bool
    {
        $language = $this->getDatabase()->getAttribute(SimpleCrud::ATTR_LOCALE);

        if (!is_null($language) && isset($this->values["{$name}_{$language}"])) {
            return true;
        }

        return isset($this->values[$name]) || isset($this->data[$name]);
    }

    public function toArray($relations = []): array
    {
        $values = $this->values;

        foreach ($relations as $name) {
            if ($row = $this->$name) {
                $values[$name] = $row->toArray();
            }
        }

        return $values;
    }

    public function edit(array $values): self
    {
        foreach ($values as $name => $value) {
            $this->__set($name, $value);
        }

        return $this;
    }

    public function save(): self
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

        return $this->save();
    }

    public function unrelate(Row ...$rows): self
    {
        $table1 = $this->table;

        foreach ($rows as $row) {
            $table2 = $row->getTable();

            //Has one
            if ($field = $table1->getJoinField($table2)) {
                $this->{$field->getName()} = null;
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

        return $this->save();
    }

    public function unrelateAll(Table ...$tables): self
    {
        $table1 = $this->table;

        foreach ($tables as $table2) {
            //Has one
            if ($field = $table1->getJoinField($table2)) {
                $this->{$field->getName()} = null;
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

        return $this->save();
    }

    public function select(Table $table): Select
    {
        //Has one
        if ($this->table->getJoinField($table)) {
            return $table->select()->one()->relatedWith($this);
        }

        return $table->select()->relatedWith($this);
    }
}
