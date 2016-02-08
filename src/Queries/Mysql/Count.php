<?php

namespace SimpleCrud\Queries\Mysql;

use SimpleCrud\Queries\Query;
use SimpleCrud\Queries\ExtendedSelectionTrait;
use SimpleCrud\Table;
use PDOStatement;
use PDO;

/**
 * Manages a database select count query.
 */
class Count extends Query
{
    use ExtendedSelectionTrait;

    /**
     * Returns the count
     * 
     * {@inheritdoc}
     *
     * @return int
     */
    public function run()
    {
        $result = $this->__invoke()->fetch();

        return (int) $result[0];
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $statement = $this->table->getDb()->execute((string) $this, $this->marks);
        $statement->setFetchMode(PDO::FETCH_NUM);

        return $statement;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        $query = "SELECT COUNT(*) FROM `{$this->table->name}`";

        $query .= $this->fromToString();
        $query .= $this->whereToString();
        $query .= $this->limitToString();

        return $query;
    }
}
