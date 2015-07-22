<?php
namespace SimpleCrud\Query\Mysql;

use SimpleCrud\RowCollection;
use SimpleCrud\Row;
use SimpleCrud\Entity;
use SimpleCrud\SimpleCrudException;
use PDOStatement;
use PDO;

/**
 * Manages a database select query in Mysql databases
 */
class Select
{
    protected $entity;

    protected $fields = [];
    protected $from = [];
    protected $where = [];
    protected $marks = [];
    protected $leftJoin = [];
    protected $orderBy = [];
    protected $limit;
    protected $offset;

    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
    }

    /**
     * Adds new extra table to the query
     * 
     * @param string     $table
     * 
     * @return self
     */
    public function from($table)
    {
        $this->from[] = $table;

        return $this;
    }

    /**
     * Adds a WHERE clause
     * 
     * @param string     $where
     * @param null|array $marks
     * 
     * @return self
     */
    public function where($where, $marks = null)
    {
        $this->where[] = $where;

        if ($marks) {
            $this->marks += $marks;
        }

        return $this;
    }

    /**
     * Adds an ORDER BY clause
     * 
     * @param string      $orderBy
     * @param string|null $direction
     * 
     * @return self
     */
    public function orderBy($orderBy, $direction = null)
    {
        if (!empty($direction)) {
            $orderBy .= ' '.$direction;
        }

        $this->orderBy[] = $orderBy;

        return $this;
    }

    /**
     * Adds a LIMIT clause
     * 
     * @param integer $limit
     * 
     * @return self
     */
    public function limit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Adds an offset to the LIMIT clause
     * 
     * @param integer $offset
     * 
     * @return self
     */
    public function offset($offset)
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Adds a LEFT JOIN clause
     * 
     * @param Entity     $entity
     * @param string     $on
     * @param array|null $marks
     * 
     * @return self
     */
    public function leftJoin(Entity $entity, $on = null, $marks = null)
    {
        if ($this->entity->getRelation($entity) !== Entity::RELATION_HAS_ONE) {
            throw new SimpleCrudException("The items '{$this->entity->table}' and '{$entity->table}' are no related or cannot be joined");
        }

        $this->leftJoin[] = [
            'entity' => $entity,
            'on' => $on
        ];

        if ($marks) {
            $this->marks += $marks;
        }

        return $this;
    }

    /**
     * Adds new marks to the query
     * 
     * @param array $marks
     * 
     * @return self
     */
    public function marks(array $marks)
    {
        $this->marks += $marks;

        return $this;
    }

    /**
     * Run the query and return a statement with the result
     * 
     * @return PDOStatement
     */
    public function run()
    {
        $statement = $this->entity->getAdapter->execute((string) $this, $this->marks);
        $statement->setFetchMode(PDO::FETCH_ASSOC);

        return $statement;
    }

    /**
     * Run the query and return all values
     * 
     * @return RowCollection
     */
    public function all()
    {
        $statement= $this->run();
        $result = $this->entity->createCollection();

        while (($row = $statement->fetch())) {
            $result = $this->createFromSelection($row);
        }

        return $result;
    }

    /**
     * Run the query and return the first value
     * 
     * @return RowCollection
     */
    public function one()
    {
        return $this->createFromSelection($this->run()->fetch());
    }

    /**
     * Build and return the query
     * 
     * @return string
     */
    public function __toString()
    {
        $query = 'SELECT';
        $query .= ' '.static::buildFields($this->entity->table, array_keys($this->entity->fields));

        foreach ($this->leftJoin as $join) {
            $query .= ', '.static::buildFields($join['entity']->table, array_keys($join['entity']->fields), $join['entity']->name);
        }

        $query .= ' FROM `'.$this->entity->table.'`';

        if (!empty($this->from)) {
            $query .= ', `'.implode('`, `', $this->from).'`';
        }

        foreach ($this->leftJoin as $join) {
            $query .= ' LEFT JOIN `'.$join['entity']->table.'`"';

            if (!empty($join['on'])) {
                $query .= ' ON ('.$join['on'],')';
            }
        }

        if (!empty($this->where)) {
            $query .= ' WHERE ('.implode(') AND (', $this->where).')';
        }

        if !(empty($this->orderBy)) {
            $query .= ' ORDER BY '.implode(', ', $this->orderBy);
        }

        if (!empty($this->limit)) {
            $query .= ' LIMIT';

            if (!empty($this->offset)) {
                $query .= ' '.$this->offset.',';
            }

            $query .= ' '.$this->limit;
        }

        return $query;
    }

    /**
     * Generates the fields/tables part of a SELECT query
     *
     * @param string      $table
     * @param array       $fields
     * @param string|null $rename
     *
     * @return string
     */
    protected static function buildFields($table, array $fields, $rename = null)
    {
        $query = [];

        foreach ($fields as $field) {
            if ($rename) {
                $query[] = "`{$table}`.`{$field}` as `{$rename}.{$field}`";
            } else {
                $query[] = "`{$table}`.`{$field}`";
            }
        }

        return implode(', ', $query);
    }
}
