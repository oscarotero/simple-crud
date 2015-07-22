<?php
namespace SimpleCrud;

use SimpleCrud\Adapters\AdapterInterface;
use PDOStatement;
use PDO;

/**
 * Manages a database entity (table)
 */
class Entity
{
    const RELATION_HAS_ONE = 1;
    const RELATION_HAS_MANY = 2;

    protected $adapter;

    public $name;
    public $table;
    public $fields;
    public $defaults;
    public $foreignKey;

    public $rowClass;
    public $rowCollectionClass;
    public $queriesNamespace;

    public function __construct(AdapterInterface $adapter, $name)
    {
        $this->adapter = $adapter;
        $this->name = $name;
    }

    /**
     * Magic method to create queries
     * 
     * @param string $name
     * @param array  $arguments
     * 
     * @throws SimpleCrudException
     * 
     * @return object
     */
    public function __call($name, $arguments)
    {
        $class = $this->queriesNamespace.'\\'.ucfirst($name);

        return new $class($this);
    }

    /**
     * init callback.
     */
    public function init()
    {
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
     * Returns the adapter associated with this entity
     *
     * @return AdapterInterface
     */
    public function getAdapter()
    {
        return $this->adapter;
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
        return $this->getAdapter()->getAttribute($name);
    }

    /**
     * Create a row instance from the result of a select query.
     *
     * @param array   $row    The selected values
     * @param boolean $expand True to expand the results (used if the select has joins)
     *
     * @return false|Row
     */
    public function createFromSelection(array $row, $expand = false)
    {
        foreach ($row as $key => &$value) {
            if (isset($this->fields[$key])) {
                $value = $this->fields[$key]->dataFromDatabase($value);
            }
        }

        if ($expand === false) {
            return ($row = $this->dataFromDatabase($row)) ? $this->create($row) : false;
        }

        $fields = $joinFields = [];

        foreach ($row as $name => $value) {
            if (strpos($name, '.') === false) {
                $fields[$name] = $value;
                continue;
            }

            list($name, $fieldName) = explode('.', $name, 2);

            if (!isset($joinFields[$name])) {
                $joinFields[$name] = [];
            }

            $joinFields[$name][$fieldName] = $value;
        }

        if (!($row = $this->dataFromDatabase($fields))) {
            return false;
        }

        $row = $this->create($row);

        foreach ($joinFields as $name => $values) {
            $row->$name = empty($values['id']) ? null : $this->getAdapter()->$name->createFromSelection($values);
        }

        return $row;
    }

    /**
     * Creates a new row instance.
     *
     * @param array   $data               The values of the row
     * @param boolean $onlyDeclaredFields Set true to discard values in undeclared fields
     *
     * @return Row
     */
    public function create(array $data = null, $onlyDeclaredFields = false)
    {
        if (!empty($data) && $onlyDeclaredFields === true) {
            $data = array_intersect_key($data, $this->fields);
        }

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
     * Executes a selection by id or by relation with other rows or collections.
     *
     * @param mixed         $id
     * @param string|array  $where
     * @param array         $marks
     * @param string|array  $orderBy
     * @param integer|array $limit
     * @param array         $joins
     * @param array         $from
     *
     * @throws SimpleCrudException on error
     *
     * @return Row|RowCollection|null
     */
    public function selectBy($id, $where = null, $marks = null, $orderBy = null, $limit = null, array $joins = null, array $from = null)
    {
        if (empty($id)) {
            return is_array($id) ? $this->createCollection() : false;
        }

        $where = empty($where) ? [] : (array) $where;
        $marks = empty($marks) ? [] : (array) $marks;

        if ($id instanceof RowInterface) {
            switch ($this->getRelation($id->entity)) {
                case self::RELATION_HAS_ONE:
                    $ids = $id->get('id');
                    $foreignKey = $id->entity->foreignKey;
                    $fetch = null;
                    break;

                case self::RELATION_HAS_MANY:
                    $ids = $id->get($this->foreignKey);
                    $foreignKey = 'id';
                    $fetch = true;
                    break;

                default:
                    throw new SimpleCrudException("The items {$this->table} and {$id->entity->table} are no related");
            }

            if (empty($ids)) {
                return ($id instanceof RowCollection) ? $this->createCollection() : null;
            }

            $where[] = "`{$this->table}`.`$foreignKey` IN (:id)";
            $marks[':id'] = $ids;

            if ($limit === null) {
                $limit = (($id instanceof RowCollection) && $fetch) ? count($ids) : $fetch;
            }
        } else {
            $where[] = 'id IN (:id)';
            $marks[':id'] = $id;

            if ($limit === null) {
                $limit = is_array($id) ? count($id) : true;
            }
        }

        return $this->select($where, $marks, $orderBy, $limit, $joins, $from);
    }

    

    /**
     * Execute a query and return the first row found.
     *
     * @param string  $query  The Mysql query to execute or the statement with the result
     * @param array   $marks  The marks passed to the statement
     * @param boolean $expand Used to expand values of rows in JOINs
     *
     * @return null|Row
     */
    public function fetchOne($query, array $marks = null, $expand = false)
    {
        return $this->createFromStatement($this->getAdapter()->execute($query, $marks), $expand);
    }

    /**
     * Execute a query and return all rows found.
     *
     * @param string  $query  The Mysql query to execute
     * @param array   $marks  The marks passed to the statement
     * @param boolean $expand Used to expand values in subrows on JOINs
     *
     * @return RowCollection
     */
    public function fetchAll($query, array $marks = null, $expand = false)
    {
        return $this->createCollectionFromStatement($this->getAdapter()->execute($query, $marks), $expand);
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
     * Prepare the data before save into database (used by update and insert).
     *
     * @param array &$data The data to save
     * @param bool  $new   True if it's a new value (insert)
     */
    private function prepareDataToDatabase(array &$data, $new)
    {
        if (!is_array($data = $this->dataToDatabase($data, $new))) {
            throw new SimpleCrudException("Data not valid");
        }

        if (array_diff_key($data, $this->fields)) {
            throw new SimpleCrudException("Invalid fields");
        }

        //Transform data before save to database
        $dbData = [];

        foreach ($data as $key => $value) {
            $dbData[$key] = $this->fields[$key]->dataToDatabase($value);
        }

        return $dbData;
    }

    /**
     * Removes unchanged data before save into database (used by update and insert).
     *
     * @param array $data          The original data
     * @param array $prepared      The prepared data
     * @param array $changedFields Array of changed fields.
     */
    private function filterDataToSave(array $data, array $prepared, array $changedFields)
    {
        $filtered = [];

        foreach ($data as $name => $value) {
            if (isset($changedFields[$name]) || ($value !== $prepared[$name])) {
                $filtered[$name] = $prepared[$name];
            }
        }

        return $filtered;
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
            if (!isset($this->getAdapter()->$entity)) {
                return;
            }

            $entity = $this->getAdapter()->$entity;
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
