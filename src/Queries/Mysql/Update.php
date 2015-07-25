<?php
namespace SimpleCrud\Queries\Mysql;

use SimpleCrud\Queries\QueryInterface;
use SimpleCrud\RowCollection;
use SimpleCrud\Row;
use SimpleCrud\Entity;
use SimpleCrud\SimpleCrudException;
use PDOStatement;
use PDO;

/**
 * Manages a database update query in Mysql databases
 */
class Update implements QueryInterface
{
    protected $entity;

    protected $data = [];
    protected $where = [];
    protected $marks = [];
    protected $limit;
    protected $offset;

    /**
     * @see QueryInterface
     * 
     * {@inheritdoc}
     */
    public static function getInstance(Entity $entity)
    {
        return new static($entity);
    }

    /**
     * @see QueryInterface
     * 
     * $entity->update($data, $where, $marks, $limit)
     * 
     * {@inheritdoc}
     */
    public static function execute(Entity $entity, array $args)
    {
        $update = self::getInstance($entity);

        $update->data($args[0]);

        if (isset($args[1])) {
            $delete->where($args[1], isset($args[2]) ? $args[2] : null);
        }

        if (isset($args[3])) {
            $select->limit($args[3]);
        }

        return $select->run();
    }

    /**
     * Constructor
     * 
     * @param Entity $entity
     */
    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
    }

    /**
     * Set the data to update
     * 
     * @param array $data
     * 
     * @return self
     */
    public function data(array $data)
    {
        $this->data = $this->entity->prepareDataToDatabase($data, false);

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
     * Run the query and return all values
     * 
     * @return PDOStatement
     */
    public function run()
    {
        $marks = [];

        foreach ($this->data as $field => $value) {
            $marks[":__{$field}"] = $value;
        }

        return $this->entity->getAdapter->execute((string) $this, $marks);
    }

    /**
     * Build and return the query
     * 
     * @return string
     */
    public function __toString()
    {
        $query = "UPDATE `{$this->entity->table}`";
        $query .= ' SET '.static::buildFields(array_keys($this->data));

        if (!empty($this->where)) {
            $query .= ' WHERE ('.implode(') AND (', $this->where).')';
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
     * Generates the data part of a UPDATE query
     *
     * @param array       $fields
     *
     * @return string
     */
    protected static function buildFields(array $fields)
    {
        $query = [];

        foreach ($fields as $field) {
            $query[] = "`{$field}` = :__{$field}";
        }

        return implode(', ', $query);
    }
}
