<?php

namespace SimpleCrud;

use ArrayAccess;
use Closure;

/**
 * Manages a database table.
 *
 * @property Fields\Field $id
 */
class Table implements ArrayAccess
{
    private $name;
    private $db;
    private $row;
    private $rowCollection;
    private $cache = [];
    private $queriesModifiers = [];
    private $fields = [];

    /**
     * Constructor.
     *
     * @param SimpleCrud $db
     * @param string     $name
     */
    final public function __construct(SimpleCrud $db, $name)
    {
        $this->db = $db;
        $this->name = $name;

        $this->setRow(new Row($this));
        $this->setRowCollection(new RowCollection($this));

        $fieldFactory = $db->getFieldFactory();

        foreach (array_keys($this->getScheme()['fields']) as $name) {
            $this->fields[$name] = $fieldFactory->get($this, $name);
        }

        $this->init();
    }

    /**
     * Debug info.
     * 
     * @return array
     */
    public function __debugInfo()
    {
        return [
            'name' => $this->name,
            'fields' => $this->fields,
        ];
    }

    /**
     * Callback used to init the table.
     */
    protected function init()
    {
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
     * @param string $name
     * @param array  $arguments
     *
     * @throws SimpleCrudException
     *
     * @return Queries\Query|null
     */
    public function __call($name, $arguments)
    {
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
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        if (array_key_exists($offset, $this->cache)) {
            return !empty($this->cache[$offset]);
        }

        return $this->count()
            ->byId($offset)
            ->limit(1)
            ->run() === 1;
    }

    /**
     * Returns a row with a specific id.
     *
     * @see ArrayAccess
     *
     * @return Row|null
     */
    public function offsetGet($offset)
    {
        if (array_key_exists($offset, $this->cache)) {
            return $this->cache[$offset];
        }

        return $this->cache[$offset] = $this->select()
            ->one()
            ->byId($offset)
            ->run();
    }

    /**
     * Store a row with a specific id.
     *
     * @see ArrayAccess
     */
    public function offsetSet($offset, $value)
    {
        //Insert on missing offset
        if ($offset === null) {
            $value['id'] = null;

            $this->insert()
                ->data($value)
                ->run();

            return;
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

    /**
     * Returns an attribute.
     *
     * @param string $name
     *
     * @return null|mixed
     */
    public function getAttribute($name)
    {
        return $this->db->getAttribute($name);
    }

    /**
     * Defines the Row class used by this table.
     *
     * @param Row $row
     */
    public function setRow(Row $row)
    {
        $this->row = $row;
    }

    /**
     * Register a custom method to the row.
     *
     * @param string  $name
     * @param Closure $method
     *
     * @return self
     */
    public function setRowMethod($name, Closure $method)
    {
        $this->row->setMethod($name, $method);

        return $this;
    }

    /**
     * Defines the RowCollection class used by this table.
     *
     * @param RowCollection $rowCollection
     */
    public function setRowCollection(RowCollection $rowCollection)
    {
        $this->rowCollection = $rowCollection;
    }

    /**
     * Register a custom method to the rowCollections.
     *
     * @param string  $name
     * @param Closure $method
     *
     * @return self
     */
    public function setRowCollectionMethod($name, Closure $method)
    {
        $this->rowCollection->setMethod($name, $method);

        return $this;
    }

    /**
     * Creates a new row instance.
     *
     * @param array $data The values of the row
     *
     * @return Row
     */
    public function create(array $data = [])
    {
        if (isset($data['id']) && isset($this->cache[$data['id']]) && is_object($this->cache[$data['id']])) {
            return $this->cache[$data['id']];
        }

        $row = clone $this->row;

        foreach ($data as $name => $value) {
            $row->$name = $value;
        }

        return $row;
    }

    /**
     * Creates a new rowCollection instance.
     *
     * @param array $data Rows added to this collection
     *
     * @return RowCollection
     */
    public function createCollection(array $data = [])
    {
        $rowCollection = clone $this->rowCollection;

        foreach ($data as $row) {
            $rowCollection[] = $row;
        }

        return $rowCollection;
    }

    /**
     * Default data converter/validator from database.
     *
     * @param array $data The values before insert to database
     * @param bool  $new  True for inserts, false for updates
     */
    public function dataToDatabase(array $data, $new)
    {
        return $data;
    }

    /**
     * Default data converter from database.
     *
     * @param array $data The database format values
     */
    public function dataFromDatabase(array $data)
    {
        return $data;
    }

    /**
     * Prepares the data from the result of a database selection.
     *
     * @param array $data
     *
     * @return Row
     */
    public function createFromDatabase(array $data)
    {
        //Get from cache
        if (isset($this->cache[$data['id']]) && is_object(isset($this->cache[$data['id']]))) {
            return $this->cache[$data['id']];
        }

        foreach ($this->fields as $name => $field) {
            $data[$name] = $field->dataFromDatabase($data[$name]);
        }

        if (!is_array($data = $this->dataFromDatabase(array_intersect_key($data, $this->fields)))) {
            throw new SimpleCrudException('Data not valid');
        }

        $row = $this->create($data);

        $this->cache($row);

        return $row;
    }

    /**
     * Prepares the data before save into database (used by update and insert).
     *
     * @param array $data
     * @param bool  $new
     *
     * @return array
     */
    public function prepareDataToDatabase(array $data, $new)
    {
        if (!is_array($data = $this->dataToDatabase($data, $new))) {
            throw new SimpleCrudException('Data not valid');
        }

        if (array_diff_key($data, $this->fields)) {
            throw new SimpleCrudException('Invalid fields');
        }

        //Transform data before save to database
        foreach ($data as $key => &$value) {
            $value = $this->fields[$key]->dataToDatabase($value);
        }

        return $data;
    }
}
