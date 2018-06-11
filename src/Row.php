<?php
declare(strict_types = 1);

namespace SimpleCrud;

use BadMethodCallException;
use JsonSerializable;
use RuntimeException;
use InvalidArgumentException;
use SimpleCrud\Engine\Common\Query\Select;
use function Latitude\QueryBuilder\field;

/**
 * Stores the data of an table row.
 */
class Row implements JsonSerializable
{
    private $table;
    private $values = [];
    private $changed;
    private $relations = [];

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
            'relations' => $this->relations,
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

    /**
     * @see JsonSerializable
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Magic method to stringify the values.
     */
    public function __toString()
    {
        return json_encode($this, JSON_NUMERIC_CHECK);
    }

    /**
     * Returns the table associated with this row
     */
    public function getTable(): Table
    {
        return $this->table;
    }

    /**
     * Returns the value of:
     * - a value field
     * - a related table
     */
    public function &__get(string $name)
    {
        //It's a value
        if ($valueName = $this->getValueName($name)) {
            return $this->values[$valueName];
        }

        //Its a relation
        if (array_key_exists($name, $this->relations)) {
            return $this->relations[$name];
        }

        $db = $this->table->getDatabase();

        if (isset($db->$name)) {
            return $this->relations[$name] = $this->select($db->$name)->run();
        }

        throw new RuntimeException(
            sprintf('Undefined property "%s" in the table "%s"', $name, $this->table->getName())
        );
    }

    /**
     * Change the value of
     * - a field
     * - a localized field
     * @param mixed $value
     */
    public function __set(string $name, $value)
    {
        //It's a value
        if ($valueName = $this->getValueName($name)) {
            if ($this->values[$valueName] !== $value) {
                $this->changed = true;
            }

            return $this->values[$valueName] = $value;
        }

        throw new RuntimeException(
            sprintf('The field %s does not exists', $name)
        );
    }

    /**
     * Check whether a value is set or not
     */
    public function __isset(string $name): bool
    {
        $valueName = $this->getValueName($name);

        return ($valueName && isset($this->values[$valueName])) || isset($this->relations[$name]);
    }

    /**
     * Removes the value of a field
     */
    public function __unset(string $name)
    {
        unset($this->relations[$name]);

        $this->__set($name, null);
    }

    /**
     * Returns an array with all fields of the row
     * @param mixed $relations
     */
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

    /**
     * Edit the values using an array
     */
    public function edit(array $values): self
    {
        foreach ($values as $name => $value) {
            $this->__set($name, $value);
        }

        return $this;
    }

    /**
     * Insert/update the row in the database
     */
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

    /**
     * Delete the row in the database
     */
    public function delete(): self
    {
        if (!empty($this->id)) {
            $this->table->delete()
                ->where(field('id')->eq($this->id))
                ->run();

            $this->id = null;
        }

        return $this;
    }

    /**
     * Relate this row with other rows
     */
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

    /**
     * Unrelate this row with other rows
     */
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

    /**
     * Unrelate this row with all rows of other tables
     */
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

    /**
     * Creates a select query of a table related with this row
     */
    public function select(Table $table): Select
    {
        //Has one
        if ($this->table->getJoinField($table)) {
            return $table->select()->one()->relatedWith($this);
        }

        return $table->select()->relatedWith($this);
    }

    /**
     * Return the real field name
     */
    private function getValueName(string $name)
    {
        if (array_key_exists($name, $this->values)) {
            return $name;
        }

        //It's a localizable field
        $language = $this->table->getDatabase()->getAttribute(Database::ATTR_LOCALE);

        if (!is_null($language)) {
            $name .= "_{$language}";

            if (array_key_exists($name, $this->values)) {
                return $name;
            }
        }
    }
}
