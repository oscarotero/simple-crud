<?php
declare(strict_types = 1);

namespace SimpleCrud;

use ArrayAccess;
use SimpleCrud\Fields\FieldInterface;
use SimpleCrud\Query\QueryInterface;

/**
 * Manages a database table.
 *
 * @property FieldInterface $id
 */
class Table implements ArrayAccess
{
    private $name;
    private $db;
    private $cache = [];
    private $fields = [];
    private $defaults = [];

    final public function __construct(Database $db, string $name)
    {
        $this->db = $db;
        $this->name = $name;

        $fieldFactory = $db->getFieldFactory();
        $fields = $db->getScheme()->getTableFields($name);

        foreach ($fields as $info) {
            $field = $fieldFactory->get($this, $info);

            $this->fields[$field->getName()] = $field;
            $this->defaults[$field->getName()] = null;
        }
    }

    public function __debugInfo(): array
    {
        return [
            'name' => $this->name,
            'fields' => $this->fields,
        ];
    }

    public function __toString()
    {
        return "`{$this->name}`";
    }

    /**
     * Get the devault values used in new rows
     */
    public function getDefaults(array $overrides = null): array
    {
        if (empty($overrides)) {
            return $this->defaults;
        }

        return array_intersect_key($overrides, $this->defaults) + $this->defaults;
    }

    /**
     * Store a row in the cache.
     */
    public function cache(Row $row): self
    {
        $this->cache[$row->id] = $row;

        return $this;
    }

    /**
     * Clear the current cache.
     */
    public function clearCache(): self
    {
        $this->cache = [];

        return $this;
    }

    /**
     * Returns whether the id is cached or not
     * @param mixed $id
     */
    public function isCached($id): bool
    {
        return array_key_exists($id, $this->cache);
    }

    /**
     * Returns a row from the cache.
     * @param mixed $id
     */
    public function getCached($id): ?Row
    {
        if (!$this->isCached($id)) {
            return null;
        }

        $row = $this->cache[$id];

        if ($row && !$row->id) {
            return $this->cache[$id] = null;
        }

        return $row;
    }

    /**
     * Magic method to create queries related with this table.
     */
    public function __call(string $name, array $arguments): QueryInterface
    {
        $class = sprintf('SimpleCrud\\Query\\%s', ucfirst($name));

        return $class::create($this, $arguments);
    }

    /**
     * Magic method to get the Field instance of a table field
     */
    public function __get(string $name): FieldInterface
    {
        if (!isset($this->fields[$name])) {
            throw new SimpleCrudException(
                sprintf('The field "%s" does not exist in the table "%s"', $name, $this->name)
            );
        }

        return $this->fields[$name];
    }

    /**
     * Magic method to check if a field exists or not.
     */
    public function __isset(string $name): bool
    {
        return isset($this->fields[$name]);
    }

    /**
     * Check if a row with a specific id exists.
     *
     * @see ArrayAccess
     * @param mixed $offset
     */
    public function offsetExists($offset): bool
    {
        if ($this->isCached($offset)) {
            return $this->getCached($offset) !== null;
        }

        return $this->count()
            ->where('id = ', $offset)
            ->limit(1)
            ->run() === 1;
    }

    /**
     * Returns a row with a specific id.
     *
     * @see ArrayAccess
     *
     * @param mixed $offset
     */
    public function offsetGet($offset): ?Row
    {
        if ($this->isCached($offset)) {
            return $this->getCached($offset);
        }

        return $this->cache[$offset] = $this->select()
            ->one()
            ->where('id = ', $offset)
            ->run();
    }

    /**
     * Store a row with a specific id.
     *
     * @see ArrayAccess
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        //Insert on missing offset
        if ($offset === null) {
            $value['id'] = null;

            return $this->insert($value)->run();
        }

        //Update if the element is cached
        $row = $this->getCached($offset);

        if (!empty($row)) {
            $row->edit($value)->save();
            return;
        }

        //Update if the element it's not cached
        if (!isset($row)) {
            $this->update()
                ->columns($value)
                ->where('id = ', $offset)
                ->run();
        }
    }

    /**
     * Remove a row with a specific id.
     *
     * @see ArrayAccess
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        $this->cache[$offset] = null;

        $this->delete()
            ->where('id = ', $offset)
            ->run();
    }

    /**
     * Returns the table name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the foreign key name.
     */
    public function getForeignKey(): string
    {
        return "{$this->name}_id";
    }

    /**
     * Returns the foreign key.
     */
    public function getJoinField(Table $table): ?FieldInterface
    {
        $field = $table->getForeignKey();

        return $this->fields[$field] ?? null;
    }

    public function getJoinTable(Table $table): ?Table
    {
        $name1 = $this->getName();
        $name2 = $table->getName();
        $name = $name1 < $name2 ? "{$name1}_{$name2}" : "{$name2}_{$name1}";

        $joinTable = $this->db->{$name} ?? null;

        if ($joinTable && $joinTable->getJoinField($this) && $joinTable->getJoinField($table)) {
            return $joinTable;
        }

        return null;
    }

    /**
     * Returns the Database instance associated with this table.
     */
    public function getDatabase(): Database
    {
        return $this->db;
    }

    /**
     * Returns all fields.
     *
     * @return FieldInterface[]
     */
    public function getFields()
    {
        return $this->fields;
    }

    public function create(array $data = [], $fromDatabase = false): Row
    {
        if (isset($data['id']) && ($row = $this->getCached($data['id']))) {
            return $row;
        }

        if ($fromDatabase) {
            $row = new Row($this, $this->rowValues($data));
            $this->cache($row);
            return $row;
        }

        return new Row($this, $data);
    }

    public function createCollection(array $rows = [], $fromDatabase = false): RowCollection
    {
        if ($fromDatabase) {
            $rows = $this->createCollection(
                array_map(
                    function ($data): Row {
                        return $this->create($data, true);
                    },
                    $rows
                )
            );
        }

        return new RowCollection($this, ...$rows);
    }

    public function databaseValues(array $data): array
    {
        foreach ($data as $key => &$value) {
            if (!isset($this->fields[$key])) {
                throw new SimpleCrudException(
                    sprintf('Invalid field (%s) in the table %s', $key, $this)
                );
            }

            $value = $this->fields[$key]->databaseValue($value);
        }

        return $data;
    }

    public function rowValues(array $data): array
    {
        foreach ($data as $key => &$value) {
            if (!isset($this->fields[$key])) {
                throw new SimpleCrudException(
                    sprintf('Invalid field (%s) in the table %s', $key, $this)
                );
            }

            $value = $this->fields[$key]->rowValue($value);
        }

        return $data;
    }
}
