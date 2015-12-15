<?php

namespace SimpleCrud;

use ArrayAccess;

/**
 * Manages a database entity (table).
 *
 * @method Queries\Mysql\Count|Queries\Sqlite\Count   count()
 * @method Queries\Mysql\Delete|Queries\Sqlite\Delete delete()
 * @method Queries\Mysql\Insert|Queries\Sqlite\Insert insert()
 * @method Queries\Mysql\Select|Queries\Sqlite\Select select()
 * @method Queries\Mysql\Sum|Queries\Sqlite\Sum       sum()
 * @method Queries\Mysql\Update|Queries\Sqlite\Update update()
 */
class Entity implements ArrayAccess
{
    const RELATION_HAS_ONE = 1;
    const RELATION_HAS_MANY = 2;
    const RELATION_HAS_BRIDGE = 3;

    protected $db;
    protected $row;
    protected $collection;
    protected $queryFactory;
    protected $fieldFactory;

    public $name;
    public $fields = [];
    public $foreignKey;

    /**
     * Constructor.
     *
     * @param string     $name
     * @param SimpleCrud $db
     */
    public function __construct($name, SimpleCrud $db, QueryFactory $queryFactory, FieldFactory $fieldFactory)
    {
        $this->db = $db;
        $this->name = $name;
        $this->foreignKey = "{$this->name}_id";

        $this->fieldFactory = $fieldFactory;
        $this->queryFactory = $queryFactory->setEntity($this);
        $this->setRow(new Row($this));
        $this->setCollection(new RowCollection($this));

        $fields = $this->db->getFields($this->name);

        foreach ($fields as $name => $type) {
            if (is_int($name)) {
                $this->setField($type);
            } else {
                $this->setField($name, $type);
            }
        }

        $this->init();
    }

    /**
     * Callback used to init the entity.
     */
    protected function init()
    {
    }

    /**
     * Register a new field type.
     *
     * @param string      $name
     * @param string|null $type
     * 
     * @return FieldInterface
     */
    protected function setField($name, $type = null)
    {
        return $this->fields[$name] = $this->fieldFactory->get($this, $name, $type);
    }

    /**
     * Magic method to create queries related with this entity.
     *
     * @param string $name
     * @param array  $arguments
     *
     * @throws SimpleCrudException
     *
     * @return QueryInterface|null
     */
    public function __call($name, $arguments)
    {
        return $this->queryFactory->get($name);
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
            ->get() === 1;
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
            ->byId($offset)
            ->one();
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
     * Returns the SimpleCrud instance associated with this entity.
     *
     * @return SimpleCrud
     */
    public function getDb()
    {
        return $this->db;
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
     * Defines the Row class used by this entity.
     *
     * @param Row $row
     */
    public function setRow(Row $row)
    {
        $this->row = $row;
    }

    /**
     * Defines the RowCollection class used by this entity.
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
            $entity = $this->getDb()->$key;

            $data[$key] = $entity->create($entity->prepareDataFromDatabase($values));
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
     * Returns the relation type of this entity with other.
     *
     * @param Entity|string $entity
     *
     * @return null|int
     */
    public function getRelation($entity)
    {
        if (is_string($entity)) {
            if (!$this->db->has($entity)) {
                return false;
            }

            $entity = $this->db->get($entity);
        }

        if ($this->hasOne($entity)) {
            return self::RELATION_HAS_ONE;
        }

        if ($this->hasMany($entity)) {
            return self::RELATION_HAS_MANY;
        }

        if ($this->hasBridge($entity)) {
            return self::RELATION_HAS_BRIDGE;
        }
    }

    /**
     * Returns whether the relation type of this entity with other is HAS_MANY.
     *
     * @param Entity $entity
     *
     * @return bool
     */
    public function hasMany(Entity $entity)
    {
        return isset($entity->fields[$this->foreignKey]);
    }

    /**
     * Returns whether the relation type of this entity with other is HAS_MANY.
     *
     * @param Entity $entity
     *
     * @return bool
     */
    public function hasOne(Entity $entity)
    {
        return isset($this->fields[$entity->foreignKey]);
    }

    /**
     * Returns whether the relation type of this entity with other is HAS_BRIDGE.
     *
     * @param Entity $entity
     *
     * @return bool
     */
    public function hasBridge(Entity $entity)
    {
        return $this->getBridge($entity) !== null;
    }

    /**
     * Returns the entity that works as a bridge between this entity and other.
     *
     * @param Entity $entity
     *
     * @return Entity|null
     */
    public function getBridge(Entity $entity)
    {
        if ($this->name < $entity->name) {
            $name = "{$this->name}_{$entity->name}";
        } else {
            $name = "{$entity->name}_{$this->name}";
        }

        if ($this->db->has($name)) {
            $bridge = $this->db->$name;

            if (isset($bridge->fields[$this->foreignKey]) && isset($bridge->fields[$entity->foreignKey])) {
                return $bridge;
            }
        }
    }
}
