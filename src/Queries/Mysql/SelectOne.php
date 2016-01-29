<?php

namespace SimpleCrud\Queries\Mysql;

use SimpleCrud\Queries\BaseQuery;
use SimpleCrud\Queries\SelectTrait;
use SimpleCrud\RowCollection;
use SimpleCrud\Entity;
use SimpleCrud\SimpleCrudException;
use PDOStatement;
use PDO;

/**
 * Manages a database select query with just one result.
 */
class SelectOne extends BaseQuery
{
    use SelectTrait;

    /**
     * {@inheritdoc}
     */
    public function __construct(Entity $entity)
    {
        parent::__construct($entity);
        $this->limit = 1;
    }

    /**
     * Prevent to change the limit clause
     */
    public function limit($limit)
    {
        throw new SimpleCrudException('SelectOne cannot modify the LIMIT clause');
    }

    /**
     * Run the query and return the first value.
     *
     * @return Row|null
     */
    public function get()
    {
        $row = $this->run()->fetch();

        if ($row !== false) {
            return $this->entity->create($this->entity->prepareDataFromDatabase($row));
        }
    }
}
