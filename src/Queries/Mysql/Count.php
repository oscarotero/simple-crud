<?php

namespace SimpleCrud\Queries\Mysql;

use SimpleCrud\Queries\Query;
use SimpleCrud\Table;
use PDO;

/**
 * Manages a database select count query.
 */
class Count extends Query
{
    use ExtendedSelectionTrait;

    /**
     * Returns the count.
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
        $statement = $this->table->getDatabase()->execute((string) $this, $this->marks);
        $statement->setFetchMode(PDO::FETCH_NUM);

        return $statement;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        $query = "SELECT COUNT(*) FROM `{$this->table->getName()}`";

        $query .= $this->fromToString();
        $query .= $this->whereToString();
        $query .= $this->limitToString();

        return $query;
    }
}
