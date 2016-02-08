<?php

namespace SimpleCrud;

use ArrayAccess;

/**
 * Manages a database table.
 */
class Table implements ArrayAccess
{
    protected $db;
    protected $row;
    protected $collection;

    public $name;
    public $fields = [];
    public $foreignKey;

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
        $this->foreignKey = "{$this->name}_id";

        $this->setRow(new Row($this));
        $this->setCollection(new RowCollection($this));

        $fieldFactory = $db->getFieldFactory();

        foreach (array_keys($this->getScheme()) as $name) {
            $this->fields[$name] = $fieldFactory->get($this, $name);
        }

        $this->init();
    }

    /**
     * Callback used to init the table.
     */
    protected function init()
    {
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
        return $this->getDb()->getQueryFactory()->get($this, $name);
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
        return $this->select()
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
        if (!empty($offset) && $this->offsetExists($offset)) {
            $this->update()
                ->data($value)
                ->byId($offset)
                ->limit(1)
                ->run();
        } else {
            $value['id'] = $offset;
            $this->insert()
                ->data($value)
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
        $this->delete()
            ->byId($offset)
            ->limit(1)
            ->run();
    }

    /**
     * Returns the SimpleCrud instance associated with this table.
     *
     * @return SimpleCrud
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * Returns the table scheme
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
     * Defines the RowCollection class used by this table.
     *
     * @param RowCollection $collection
     */
    public function setCollection(RowCollection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * Creates a new row instance.
     *
     * @param array $data The values of the row
     *
     * @return Row
     */
    public function create(array $data = null)
    {
        $row = clone $this->row;

        if ($data !== null) {
            $row->set($data);
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
    public function createCollection(array $data = null)
    {
        $collection = clone $this->collection;

        if ($data !== null) {
            $collection->add($data);
        }

        return $collection;
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
     * @return array
     */
    public function prepareDataFromDatabase(array $data)
    {
        $joins = [];

        foreach ($data as $key => &$value) {
            if (isset($this->fields[$key])) {
                $value = $this->fields[$key]->dataFromDatabase($value);
                continue;
            }

            if (strpos($key, '.') !== false) {
                list($name, $field) = explode('.', $key, 2);

                if (!isset($joins[$name])) {
                    $joins[$name] = [];
                }

                $joins[$name][$field] = $value;

                unset($data[$key]);
            }
        }

        if (!is_array($data = $this->dataFromDatabase($data))) {
            throw new SimpleCrudException('Data not valid');
        }

        //handle left-joins
        foreach ($joins as $key => $values) {
            $table = $this->getDb()->$key;

            $data[$key] = $table->create($table->prepareDataFromDatabase($values));
        }

        return $data;
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

    /**
     * Returns if a row of this table can be related with many rows of other table
     *
     * @param Table $table
     *
     * @return bool
     */
    public function hasMany(Table $table)
    {
        return $table->hasOne($this) || ($table->getBridge($this) !== null);
    }

    /**
     * Returns if a row of this table can be related with just one row of other table
     *
     * @param Table $table
     *
     * @return bool
     */
    public function hasOne(Table $table)
    {
        return isset($this->fields[$table->foreignKey]);
    }

    /**
     * Returns the table that works as a bridge between this table and other.
     *
     * @param Table $table
     *
     * @return Table|null
     */
    public function getBridge(Table $table)
    {
        if ($this->name < $table->name) {
            $name = "{$this->name}_{$table->name}";
        } else {
            $name = "{$table->name}_{$this->name}";
        }

        if (isset($this->db->$name)) {
            $bridge = $this->db->$name;

            if ($bridge->hasOne($this) && $bridge->hasOne($table)) {
                return $bridge;
            }
        }
    }
}
