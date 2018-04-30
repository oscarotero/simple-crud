<?php
declare(strict_types = 1);

namespace SimpleCrud;

use ArrayAccess;
use function Latitude\QueryBuilder\field;

/**
 * Manages a database table.
 *
 * @property Fields\Field $id
 */
class Table implements ArrayAccess
{
    private $name;
    private $db;
    private $cache = [];
    private $fields = [];
    private $defaults = [];

    final public function __construct(SimpleCrud $db, string $name)
    {
        $this->db = $db;
        $this->name = $name;

        $fieldFactory = $db->getFieldFactory();

        foreach (array_keys($this->getScheme()['fields']) as $name) {
            $this->fields[$name] = $fieldFactory->get($this, $name);
            $this->defaults[$name] = null;
        }
    }

    public function __debugInfo(): array
    {
        return [
            'name' => $this->name,
            'fields' => $this->fields,
        ];
    }

    public function getDefaults(array $overrides = null): array
    {
        if (empty($overrides)) {
            return $this->defaults;
        }

        return array_intersect_key($overrides, $this->defaults) + $this->defaults;
    }

    /**
     * Store a row in the cache.
     *
     * @param Row $row
     */
    public function cache(Row $row)
    {
        $this->cache[$row->id] = $row;
    }

    /**
     * Clear the current cache.
     */
    public function clearCache()
    {
        $this->cache = [];
    }

    /**
     * Register a new query modifier.
     *
     * @param string   $name
     * @param callable $modifier
     */
    public function addQueryModifier($name, callable $modifier)
    {
        if (!isset($this->queriesModifiers[$name])) {
            $this->queriesModifiers[$name] = [];
        }

        $this->queriesModifiers[$name][] = $modifier;
    }

    /**
     * Magic method to create queries related with this table.
     *
     * @throws SimpleCrudException
     *
     * @return Queries\Query
     */
    public function __call(string $name, array $arguments)
    {
        $class = $this->getDatabase()->getEngineNamespace().'Query\\'.ucfirst($name);

        return $class::create($this, $arguments);
        $query = $this->getDatabase()->getQueryFactory()->get($this, $name);

        if (isset($this->queriesModifiers[$name])) {
            foreach ($this->queriesModifiers[$name] as $modifier) {
                $modifier($query);
            }
        }

        return $query;
    }

    /**
     * Magic method to get the Field instance of a table field
     *
     * @param string $name The field name
     *
     * @throws SimpleCrudException
     *
     * @return Fields\Field
     */
    public function __get($name)
    {
        if (!isset($this->fields[$name])) {
            throw new SimpleCrudException(sprintf('The field "%s" does not exist in the table "%s"', $name, $this->name));
        }

        return $this->fields[$name];
    }

    /**
     * Magic method to check if a field exists or not.
     *
     * @param string $name
     *
     * @return bool
     */
    public function __isset($name)
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
        if (array_key_exists($offset, $this->cache)) {
            return !empty($this->cache[$offset]);
        }

        return $this->count()
            ->where(field('id')->eq($offset))
            ->limit(1)
            ->run() === 1;
    }

    /**
     * Returns a row with a specific id.
     *
     * @see ArrayAccess
     *
     * @param  mixed    $offset
     * @return Row|null
     */
    public function offsetGet($offset)
    {
        if (array_key_exists($offset, $this->cache)) {
            return $this->cache[$offset];
        }

        return $this->cache[$offset] = $this->select()
            ->one()
            ->where(field('id')->eq($offset))
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
        if (isset($this->cache[$offset])) {
            $row = $this->cache[$offset];

            foreach ($value as $name => $val) {
                $row->$name = $val;
            }

            $row->save();

            return;
        }

        //Update if the element it's not cached
        if ($this->offsetExists($offset)) {
            $this->update()
                ->data($value)
                ->byId($offset)
                ->limit(1)
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
        unset($this->cache[$offset]);

        $this->delete()
            ->byId($offset)
            ->limit(1)
            ->run();
    }

    /**
     * Returns the table name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the SimpleCrud instance associated with this table.
     *
     * @return SimpleCrud
     */
    public function getDatabase()
    {
        return $this->db;
    }

    /**
     * Returns all fields.
     *
     * @return Fields\Field[]
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Returns the table scheme.
     *
     * @return array
     */
    public function getScheme()
    {
        return $this->db->getScheme()[$this->name];
    }

    public function create(array $data = [], $fromDatabase = false): Row
    {
        if (isset($data['id']) && isset($this->cache[$data['id']]) && is_object($this->cache[$data['id']])) {
            return $this->cache[$data['id']];
        }

        if ($fromDatabase) {
            $row = new Row($this, $this->rowValues($data));
            $this->cache($row);
            return $row;
        }

        return new Row($this, $data);
    }

    public function createCollection(array $data = []): RowCollection
    {
        return new RowCollection($this, $data);
    }

    public function databaseValues(array $data): array
    {
        foreach ($data as $key => &$value) {
            if (!isset($this->fields[$key])) {
                throw new SimpleCrudException(sprintf('Invalid field (%s)', $key));
            }

            $value = $this->fields[$key]->databaseValue($value, $data);
        }

        return $data;
    }

    public function rowValues(array $data): array
    {
        foreach ($data as $key => &$value) {
            if (!isset($this->fields[$key])) {
                throw new SimpleCrudException(sprintf('Invalid field (%s)', $key));
            }

            $value = $this->fields[$key]->rowValue($value, $data);
        }

        return $data;
    }
}
