<?php
namespace SimpleCrud;

use SimpleCrud\SimpleCrud;
use PDOStatement;
use PDO;

/**
 * Manages a database entity (table)
 */
class Entity
{
    const RELATION_HAS_ONE = 1;
    const RELATION_HAS_MANY = 2;

    protected $db;

    public $name;
    public $table;
    public $fields;
    public $defaults;
    public $foreignKey;

    public $rowClass = 'SimpleCrud\\Row';
    public $rowCollectionClass = 'SimpleCrud\\RowCollection';

    public static function getInstance($name, SimpleCrud $db)
    {
        $entity = new static($db);
        $entity->name = $name;

        if (empty($entity->table)) {
            $entity->table = $name;
        }

        if (empty($entity->foreignKey)) {
            $entity->foreignKey = "{$entity->table}_id";
        }

        $fields = $entity->fields ?: $entity->fields();

        foreach ($fields as $name => $type) {
            $entity->fields[$name] = $db->getFactory()->getField($entity, $type);
        }

        return $entity;
    }


    public function __construct(SimpleCrud $db)
    {
        $this->db = $db;
    }

    /**
     * Magic method to create queries
     * 
     * @param string $name
     * @param array  $arguments
     * 
     * @throws SimpleCrudException
     * 
     * @return QueryInterface|mixed
     */
    public function __call($name, $arguments)
    {
        if (strpos($name, 'query')) {
            $name = substr($name, 5);

            return $this->db->getFactory()->getQuery($this, $name);
        }

        $class = $this->db->getFactory()->getQueryClass($name);

        if ($class) {
            return $class::execute($this, $arguments);
        }
    }

    /**
     * Returns an array with the defaults values.
     *
     * @return array
     */
    public function getDefaults()
    {
        return array_fill_keys(array_keys($this->fields), null);
    }

    /**
     * Returns the SimpleCrud instance associated with this entity
     *
     * @return SimpleCrud
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * Returns an attribute
     * 
     * @param string $name
     * 
     * @return null|mixed
     */
    public function getAttribute($name)
    {
        return $this->getDb()->getAttribute($name);
    }

    /**
     * Creates a new row instance.
     *
     * @param array   $data               The values of the row
     * @param boolean $onlyDeclaredFields Set true to discard values in undeclared fields
     *
     * @return Row
     */
    public function create(array $data = null)
    {
        return new $this->rowClass($this, $data);
    }

    /**
     * Creates a new rowCollection instance.
     *
     * @param array $rows Rows added to this collection
     *
     * @return RowCollection
     */
    public function createCollection(array $rows = null)
    {
        $collection = new $this->rowCollectionClass($this);

        if ($rows !== null) {
            $collection->add($rows);
        }

        return $collection;
    }

    /**
     * Default data converter/validator from database.
     *
     * @param array   $data The values before insert to database
     * @param boolean $new  True for inserts, false for updates
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
     * Prepares the data from the result of a database selection
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
            throw new SimpleCrudException("Data not valid");
        }

        $data = $this->dataFromDatabase($data);

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
            throw new SimpleCrudException("Data not valid");
        }

        if (array_diff_key($data, $this->fields)) {
            throw new SimpleCrudException("Invalid fields");
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
     * @return null|integer
     */
    public function getRelation($entity)
    {
        if (is_string($entity)) {
            if (!isset($this->getDb()->$entity)) {
                return;
            }

            $entity = $this->getDb()->$entity;
        }

        if (isset($entity->fields[$this->foreignKey])) {
            return self::RELATION_HAS_MANY;
        }

        if (isset($this->fields[$entity->foreignKey])) {
            return self::RELATION_HAS_ONE;
        }
    }

    /**
     * Returns whether the relation type of this entity with other is HAS_MANY.
     *
     * @param Entity|string $entity
     *
     * @return boolean
     */
    public function hasMany($entity)
    {
        return $this->getRelation($entity) === self::RELATION_HAS_MANY;
    }

    /**
     * Returns whether the relation type of this entity with other is HAS_MANY.
     *
     * @param Entity|string $entity
     *
     * @return boolean
     */
    public function hasOne($entity)
    {
        return $this->getRelation($entity) === self::RELATION_HAS_ONE;
    }
}
