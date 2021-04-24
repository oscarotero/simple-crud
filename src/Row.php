<?php
declare(strict_types = 1);

namespace SimpleCrud;

use BadMethodCallException;
use JsonSerializable;
use RuntimeException;
use SimpleCrud\Events\BeforeSaveRow;
use SimpleCrud\Queries\Select;

/**
 * Stores the data of an table row.
 */
class Row implements JsonSerializable
{
    private $table;
    private $values = [];
    private $changes = [];
    private $data = [];

    public function __construct(Table $table, array $values)
    {
        $this->table = $table;

        if (empty($values['id'])) {
            $this->values = $table->getDefaults();
            $this->changes = $table->getDefaults($values);
            unset($this->changes['id']);
        } else {
            $this->values = $table->getDefaults($values);
        }
    }

    public function __debugInfo(): array
    {
        return [
            'table' => (string) $this->table,
            'values' => $this->values,
            'changes' => $this->changes,
            'data' => $this->data,
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

    public function setData(array $data): self
    {
        $this->data = $data + $this->data;

        return $this;
    }

    /**
     * @param Row|RowCollection|null $row
     */
    public function link(Table $table, $row = null): self
    {
        return $this->setData([$table->getName() => $row]);
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
        if ($name === 'id') {
            return $this->values['id'];
        }

        //It's a value
        if ($valueName = $this->getValueName($name)) {
            $value = $this->getValue($valueName);
            return $value;
        }

        //It's custom data
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }

        $db = $this->table->getDatabase();

        if (isset($db->$name)) {
            $this->setData([
                $name => $this->select($db->$name)->run(),
            ]);

            return $this->data[$name];
        }

        throw new RuntimeException(
            sprintf('Undefined property "%s" in the table %s', $name, $this->table)
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
        if ($name === 'id') {
            if (!is_null($this->values['id']) && !is_null($value)) {
                throw new RuntimeException('The field "id" cannot be overrided');
            }

            $this->values['id'] = $value;

            return $value;
        }

        //It's a value
        if ($valueName = $this->getValueName($name)) {
            if ($this->values[$valueName] === $value) {
                unset($this->changes[$valueName]);
            } else {
                $this->changes[$valueName] = $value;
            }

            return $value;
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

        if ((isset($valueName) && !is_null($this->getValue($valueName))) || isset($this->data[$name])) {
            return true;
        }

        // It's a relation
        $db = $this->table->getDatabase();

        if (isset($db->$name)) {
            $table = $db->$name;

            // Has one
            if ($field = $this->table->getJoinField($table)) {
                return !is_null($this->getValue($field->getName()));
            }

            // Has many or many-to-many always return true
            // because even if it's empty, it returns always a RowCollection
            if ($table->getJoinField($this->table) || $this->table->getJoinTable($table)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Removes the value of a field
     */
    public function __unset(string $name)
    {
        unset($this->data[$name]);

        $this->__set($name, null);
    }

    /**
     * Reload the data from the database
     */
    public function reload(bool $keepChanges = false): self
    {
        $select = $this->table->select()->where('id = ', $this->id);
        $values = $select()->fetch(\PDO::FETCH_ASSOC);
        $this->values = $this->table->format($values);

        if (!$keepChanges) {
            $this->changes = [];
        }

        return $this;
    }

    /**
     * Returns an array with all fields of the row
     */
    public function toArray(): array
    {
        return $this->changes + $this->values;
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
     * Returns the id of the row.
     * If it does not exist (because it is not saved in the database yet),
     * execute a save() first
     */
    public function id(): int
    {
        if (empty($this->id)) {
            $this->save();
        }

        return $this->id;
    }

    /**
     * Insert/update the row in the database
     */
    public function save(): self
    {
        if (!empty($this->changes)) {
            $eventDispatcher = $this->table->getEventDispatcher();

            if ($eventDispatcher) {
                $eventDispatcher->dispatch(new BeforeSaveRow($this));
            }

            if (empty($this->id)) {
                $this->id = $this->table->insert($this->toArray())->run();
            } elseif (empty($this->changes)) {
                return $this;
            } else {
                $this->table->update($this->changes)
                    ->where('id = ', $this->id)
                    ->run();
            }

            $this->values = $this->toArray();
            $this->changes = [];
            $this->table->cache($this);
        }

        return $this;
    }

    /**
     * Delete the row in the database
     */
    public function delete(): self
    {
        $id = $this->id;

        if (!empty($id)) {
            $this->table->delete()
                ->where('id = ', $id)
                ->run();

            $this->values['id'] = null;
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
                $this->{$field->getName()} = $row->id();
                continue;
            }

            //Has many
            if ($field = $table2->getJoinField($table1)) {
                $row->{$field->getName()} = $this->id();
                $row->save();
                continue;
            }

            //Has many to many
            if ($joinTable = $table1->getJoinTable($table2)) {
                $joinTable->insert([
                    $joinTable->getJoinField($table1)->getName() => $this->id(),
                    $joinTable->getJoinField($table2)->getName() => $row->id(),
                ])
                ->orIgnore()
                ->run();

                continue;
            }

            throw new RuntimeException(
                sprintf('The tables %s and %s are not related', $table1, $table2)
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
                    ->where("{$joinTable->getJoinField($table1)} = ", $this->id)
                    ->where("{$joinTable->getJoinField($table2)} = ", $row->id)
                    ->run();

                continue;
            }

            throw new RuntimeException(
                sprintf('The tables %s and %s are not related', $table1, $table2)
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
                    ->where("{$joinTable->getJoinField($table1)} = ", $this->id)
                    ->where("{$joinTable->getJoinField($table2)} IS NOT NULL")
                    ->run();

                continue;
            }

            throw new RuntimeException(
                sprintf('The tables %s and %s are not related', $table1, $table2)
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
    private function getValueName(string $name): ?string
    {
        if (array_key_exists($name, $this->values)) {
            return $name;
        }

        //It's a localizable field
        $language = $this->table->getDatabase()->getConfig(Database::CONFIG_LOCALE);

        if (!is_null($language)) {
            $name .= "_{$language}";

            if (array_key_exists($name, $this->values)) {
                return $name;
            }
        }

        return null;
    }

    private function getValue(string $name)
    {
        return array_key_exists($name, $this->changes) ? $this->changes[$name] : $this->values[$name];
    }
}
