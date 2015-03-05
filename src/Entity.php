<?php
namespace SimpleCrud;

use SimpleCrud\Adapters\AdapterInterface;
use PDOStatement;
use PDO;

/**
 * SimpleCrud\Entity.
 *
 * Manages a database entity (table)
 */
class Entity
{
    const RELATION_HAS_ONE = 1;
    const RELATION_HAS_MANY = 2;

    public $adapter;

    public $name;
    public $table;
    public $fields;
    public $defaults;
    public $foreignKey;

    public $rowClass;
    public $rowCollectionClass;

    private $fieldsInfo = [];

    public function __construct(AdapterInterface $adapter, $name)
    {
        $this->adapter = $adapter;
        $this->name = $name;
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
            $row->$name = empty($values['id']) ? null : $this->manager->$name->createFromSelection($values);
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
     * Executes a SELECT in the database.
     *
     * @param string/array $where
     * @param null|array   $marks
     * @param string/array $orderBy
     * @param int/array    $limit
     * @param null|array   $joins   Optional entities to join
     * @param null|array   $from    Extra tables used in the query
     *
     * @return mixed The row or rowcollection with the result or null
     */
    public function select($where = '', array $marks = null, $orderBy = null, $limit = null, array $joins = null, array $from = null)
    {
        if ($limit === 0) {
            return $this->createCollection();
        }

        $selectFields = [
            $this->table => array_keys($this->fields),
        ];

        if ($from) {
            foreach ($from as $table) {
                $selectFields[$table] = [];
            }
        }

        $selectJoins = [];

        if ($joins !== null) {
            foreach ($joins as $name => $options) {
                if (!is_array($options)) {
                    $name = $options;
                    $options = [];
                }

                $entity = $this->manager->$name;
                $relation = $this->getRelation($entity);

                if ($relation !== self::RELATION_HAS_ONE) {
                    throw new SimpleCrudException("The items '{$this->table}' and '{$entity->table}' are no related or cannot be joined");
                }
                $currentJoin = [
                    'table' => $entity->table,
                    'name' => $entity->name,
                    'fields' => array_keys($entity->fields),
                    'on' => ["`{$entity->table}`.`id` = `{$this->table}`.`{$entity->foreignKey}`"],
                ];

                if (!empty($options['on'])) {
                    $currentJoin['on'][] = $options['on'];

                    if (!empty($options['marks'])) {
                        $marks = array_replace($marks, $options['marks']);
                    }
                }

                $selectJoins[] = $currentJoin;
            }
        }

        $statement = $this->adapter->executeSelect($selectFields, $selectJoins, $where, $marks, $orderBy, $limit);

        if ($limit === true || (isset($limit[1]) && $limit[1] === true)) {
            return $this->createFromStatement($statement);
        }

        return $this->createCollectionFromStatement($statement);
    }

    /**
     * Executes a selection by id or by relation with other rows or collections.
     *
     * @param mixed        $id      The id/ids, row or rowCollection used to select
     * @param string/array $where
     * @param array        $marks
     * @param string/array $orderBy
     * @param int/array    $limit
     * @param array        $joins   Optional entities to join
     *
     * @return mixed The row or rowcollection with the result or null
     */
    public function selectBy($id, $where = null, $marks = null, $orderBy = null, $limit = null, array $joins = null, array $from = null)
    {
        if (empty($id)) {
            return is_array($id) ? $this->createCollection() : false;
        }

        $where = empty($where) ? [] : (array) $where;
        $marks = empty($marks) ? [] : (array) $marks;

        if ($id instanceof RowInterface) {
            if (!($relation = $this->getRelation($id->entity))) {
                throw new SimpleCrudException("The items {$this->table} and {$id->entity->table} are no related");
            }

            if ($relation === self::RELATION_HAS_ONE) {
                $ids = $id->get('id');
                $foreignKey = $id->entity->foreignKey;
                $fetch = null;
            } elseif ($relation === self::RELATION_HAS_MANY) {
                $ids = $id->get($this->foreignKey);
                $foreignKey = 'id';
                $fetch = true;
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
     * Execute a count query in the database.
     *
     * @param string/array $where
     * @param array        $marks
     * @param int/array    $limit
     *
     * @return int
     */
    public function count($where = null, $marks = null, $limit = null)
    {
        return $this->adapter->count($this->table, $where, $marks, $limit);
    }

    /**
     * Execute a query and return the first row found.
     *
     * @param PDOStatement $statement
     * @param boolean      $expand    Used to expand values of rows in JOINs
     *
     * @return null|Row
     */
    public function createFromStatement(PDOStatement $statement, $expand = false)
    {
        $statement->setFetchMode(PDO::FETCH_ASSOC);

        if (($data = $statement->fetch())) {
            return $this->createFromSelection($data, $expand);
        }
    }

    /**
     * Execute a query and return the first row found.
     *
     * @param PDOStatement $statement
     * @param boolean      $expand    Used to expand values of rows in JOINs
     *
     * @return RowCollection
     */
    public function createCollectionFromStatement(PDOStatement $statement, $expand = false)
    {
        $statement->setFetchMode(PDO::FETCH_ASSOC);

        $result = [];

        while (($row = $statement->fetch())) {
            if (($row = $this->createFromSelection($row, $expand))) {
                $result[] = $row;
            }
        }

        return $this->createCollection($result);
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
        return $this->createFromStatement($this->adapter->execute($query, $marks), $expand);
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
        return $this->createCollectionFromStatement($this->adapter->execute($query, $marks), $expand);
    }

    /**
     * Default data converter/validator from database.
     *
     * @param array $data The values before insert to database
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
     * Executes an 'insert' query in the database.
     *
     * @param array   $data         The values to insert
     * @param boolean $duplicateKey Set true if you can avoid duplicate key errors
     *
     * @return array The new values of the inserted row
     */
    public function insert(array $data, $duplicateKey = false)
    {
        $preparedData = $this->prepareDataToDatabase($data, true);

        unset($preparedData['id']);

        $data['id'] = $this->adapter->executeTransaction(function () use ($preparedData, $duplicateKey) {
            $this->adapter->insert($this->table, $preparedData, $duplicateKey);

            return $this->adapter->lastInsertId();
        });

        return $data;
    }

    /**
     * Executes an 'update' query in the database.
     *
     * @param array        $data  The values to update
     * @param string/array $where
     * @param array        $marks
     * @param int/array    $limit
     *
     * @return array The new values of the updated row
     */
    public function update(array $data, $where = null, $marks = null, $limit = null, array $changedFields = null)
    {
        $originalData = $data;
        $preparedData = $this->prepareDataToDatabase($data, true);

        if ($changedFields !== null) {
            $preparedData = $this->filterDataToSave($originalData, $preparedData, $changedFields);
        }

        unset($originalData, $preparedData['id']);

        if (empty($preparedData)) {
            return $data;
        }

        $this->adapter->executeTransaction(function () use ($preparedData, $where, $marks, $limit) {
            $this->adapter->update($this->table, $preparedData, $where, $marks, $limit);
        });

        return $data;
    }

    /**
     * Execute a delete query in the database.
     *
     * @param string/array $where
     * @param array        $marks
     * @param int/array    $limit
     */
    public function delete($where = null, $marks = null, $limit = null)
    {
        $this->adapter->executeTransaction(function () use ($where, $marks, $limit) {
            $this->adapter->delete($this->table, $where, $marks, $limit);
        });
    }

    /**
     * Check if this entity is related with other.
     *
     * @param SimpleCrud\Entity / string $entity The entity object or name
     *
     * @return boolean
     */
    public function isRelated($entity)
    {
        if (!($entity instanceof Entity)) {
            if (!isset($this->adapter->$entity)) {
                return false;
            }

            $entity = $this->adapter->$entity;
        }

        return ($this->getRelation($entity) !== null);
    }

    /**
     * Returns the relation type of this entity with other.
     *
     * @param SimpleCrud\Entity $entity
     *
     * @return int One of the RELATION_* constants values or null
     */
    public function getRelation(Entity $entity)
    {
        if (isset($entity->fields[$this->foreignKey])) {
            return self::RELATION_HAS_MANY;
        }

        if (isset($this->fields[$entity->foreignKey])) {
            return self::RELATION_HAS_ONE;
        }
    }
}
