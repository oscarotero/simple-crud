<?php
namespace SimpleCrud\Query\Mysql;

use SimpleCrud\RowCollection;
use SimpleCrud\Row;
use SimpleCrud\Entity;
use SimpleCrud\SimpleCrudException;
use PDOStatement;
use PDO;

/**
 * Manages a database select count query in Mysql databases
 */
class Count
{
    protected $entity;

    protected $where = [];
    protected $marks = [];
    protected $limit;

    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
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
        $statement->setFetchMode(PDO::FETCH_NUM);

        return $statement;
    }

    /**
     * Run the query and return the value
     * 
     * @return integer
     */
    public function get()
    {
        $result = $this->run()->fetch();

        return (int) $result[0];
    }

    /**
     * Build and return the query
     * 
     * @return string
     */
    public function __toString()
    {
        $query = 'SELECT COUNT(*)';
        $query .= ' FROM `'.implode('`, `', $this->entity->table).'`';

        if (!empty($this->where)) {
            $query .= ' WHERE ('.implode(') AND (', $this->where).')';
        }

        if (!empty($this->limit)) {
            $query .= ' LIMIT '.$this->limit;
        }

        return $query;
    }
}
