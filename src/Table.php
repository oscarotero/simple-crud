<?php
declare(strict_types = 1);

namespace SimpleCrud;

use ArrayAccess;
use Psr\EventDispatcher\EventDispatcherInterface;
use SimpleCrud\Events\BeforeCreateRow;
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
    private $eventDispatcher;

    protected const ROW_CLASS = Row::class;
    protected const ROWCOLLECTION_CLASS = RowCollection::class;

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

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): self
    {
        $this->eventDispatcher = $eventDispatcher;

        return $this;
    }

    public function getEventDispatcher(): ?EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    /**
     * Get the devault values used in new rows
     */
    public function getDefaults(array $overrides = null): array
    {
        if (empty($overrides)) {
            return $this->defaults;
        }

        $diff = array_diff_key($overrides, $this->fields);

        if ($diff) {
            throw new SimpleCrudException(
                sprintf('The field %s does not exist in the table %s', implode(array_keys($diff)), $this)
            );
        }

        return $overrides + $this->defaults;
    }

    /**
     * Store a row in the cache.
     */
    public function cache(Row $row): Row
    {
        if ($row->id) {
            $this->cache[$row->id] = $row;
        }

        return $row;
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
                sprintf('The field `%s` does not exist in the table %s', $name, $this)
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
    public function offsetSet($offset, $value): Row
    {
        //Insert on missing offset
        if ($offset === null) {
            $value['id'] = null;

            return $this->create($value)->save();
        }

        //Update if the element is cached and exists
        $row = $this->getCached($offset);

        if ($row) {
            return $row->edit($value)->save();
        }

        //Update if the element it's not cached
        if (!$this->isCached($row)) {
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

    public function create(array $data = []): Row
    {
        if (isset($data['id']) && ($row = $this->getCached($data['id']))) {
            return $row;
        }

        $eventDispatcher = $this->getEventDispatcher();

        if ($eventDispatcher) {
            $event = new BeforeCreateRow($data);
            $eventDispatcher->dispatch($event);
            $data = $event->getData();
        }

        $class = self::ROW_CLASS;
        return $this->cache(new $class($this, $data));
    }

    public function createCollection(array $rows = []): RowCollection
    {
        $rows = array_map(
            function ($data): Row {
                return is_array($data) ? $this->create($data) : $data;
            },
            $rows
        );

        $class = self::ROWCOLLECTION_CLASS;
        return new $class($this, ...$rows);
    }
}
