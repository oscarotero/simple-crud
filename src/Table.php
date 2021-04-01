<?php
declare(strict_types = 1);

namespace SimpleCrud;

use ArrayAccess;
use Countable;
use Psr\EventDispatcher\EventDispatcherInterface;
use SimpleCrud\EventDispatcher\Dispatcher;
use SimpleCrud\Events\BeforeCreateRow;
use SimpleCrud\Events\CreateDeleteQuery;
use SimpleCrud\Events\CreateInsertQuery;
use SimpleCrud\Events\CreateSelectQuery;
use SimpleCrud\Events\CreateUpdateQuery;
use SimpleCrud\Fields\Field;
use SimpleCrud\Queries\Delete;
use SimpleCrud\Queries\Insert;
use SimpleCrud\Queries\Select;
use SimpleCrud\Queries\SelectAggregate;
use SimpleCrud\Queries\Update;

/**
 * Manages a database table.
 *
 * @property Field $id
 */
class Table implements ArrayAccess, Countable
{
    private $name;
    private $db;
    private $cache = [];
    private $fields = [];
    private $defaults = [];
    private $eventDispatcher;
    private $fieldFactories;

    protected const ROW_CLASS = Row::class;
    protected const ROWCOLLECTION_CLASS = RowCollection::class;

    final public function __construct(Database $db, string $name)
    {
        $this->db = $db;
        $this->name = $name;

        $this->fieldFactories = $db->getFieldFactories();
        $fields = $db->getScheme()->getTableFields($name);

        foreach ($fields as $info) {
            $field = $this->createField($info);

            $this->fields[$field->getName()] = $field;
            $this->defaults[$field->getName()] = null;
        }

        $this->init();
    }

    protected function init(): void
    {
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

    public function getEventDispatcher(): EventDispatcherInterface
    {
        if ($this->eventDispatcher === null) {
            $this->eventDispatcher = new Dispatcher();
        }

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

        if (!empty($diff)) {
            throw new SimpleCrudException(
                sprintf('The field %s does not exist in the table %s', implode(array_keys($diff)), $this)
            );
        }

        return $overrides + $this->defaults;
    }

    /**
     * Format selected data from the database
    */
    public function format(array $values): array
    {
        foreach ($this->fields as $name => $field) {
            if (array_key_exists($name, $values)) {
                $values[$name] = $field->format($values[$name]);
            }
        }

        return $values;
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

    public function delete(): Delete
    {
        $query = new Delete($this);

        if ($eventDispatcher = $this->getEventDispatcher()) {
            $eventDispatcher->dispatch(new CreateDeleteQuery($query));
        }

        return $query;
    }

    public function insert(array $data = []): Insert
    {
        $query = new Insert($this, $data);

        if ($eventDispatcher = $this->getEventDispatcher()) {
            $eventDispatcher->dispatch(new CreateInsertQuery($query));
        }

        return $query;
    }

    public function select(): Select
    {
        $query = new Select($this);

        if ($eventDispatcher = $this->getEventDispatcher()) {
            $eventDispatcher->dispatch(new CreateSelectQuery($query));
        }

        return $query;
    }

    public function selectAggregate(string $function, string $field = 'id', string $as = null): SelectAggregate
    {
        $query = new SelectAggregate($this, $function, $field, $as);

        if ($eventDispatcher = $this->getEventDispatcher()) {
            $eventDispatcher->dispatch(new CreateSelectQuery($query));
        }

        return $query;
    }

    public function update(array $data = []): Update
    {
        $query = new Update($this, $data);

        if ($eventDispatcher = $this->getEventDispatcher()) {
            $eventDispatcher->dispatch(new CreateUpdateQuery($query));
        }

        return $query;
    }

    /**
     * Magic method to get the Field instance of a table field
     */
    public function __get(string $name): Field
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
     * @see Countable
     */
    public function count(): int
    {
        return $this->selectAggregate('COUNT')->run();
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

        return $this->selectAggregate('COUNT')
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
    public function offsetSet($offset, $value): void
    {
        //Insert on missing offset
        if ($offset === null) {
            $value['id'] = null;

            $this->create($value)->save();
            return;
        }

        //Update if the element is cached and exists
        $row = $this->getCached($offset);

        if ($row) {
            $row->edit($value)->save();
            return;
        }

        //Update if the element it's not cached
        if (!$this->isCached($row)) {
            $this->update($value)
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
    public function getJoinField(Table $table): ?Field
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
     * @return Field[]
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @deprecated
     */
    public function __call(string $name, array $args): ?Row
    {
        return $this->get([$name => $args[0]]);
    }

    /**
     * Search a row with some values
     */
    public function get(array $data): ?Row
    {
        $query = $this->select()->one();

        foreach ($data as $name => $value) {
            $field = $this->__get($name);

            $query->where("{$field} = ", $value);
        }

        return $query->run();
    }

    /**
     * Search a row with some values or create one if it does not exist
     */
    public function getOrCreate(array $data): Row
    {
        return $this->get($data) ?: $this->create($data);
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

        $class = static::ROW_CLASS;
        return $this->cache(new $class($this, $data));
    }

    public function createCollection(array $rows = []): RowCollection
    {
        $class = static::ROWCOLLECTION_CLASS;
        return new $class($this, ...$rows);
    }

    public function createField(array $info): Field
    {
        foreach ($this->fieldFactories as $fieldFactory) {
            if ($field = $fieldFactory->create($this, $info)) {
                return $field;
            }
        }

        return new Field($this, $info);
    }
}
