<?php

namespace SimpleCrud\Queries\Mysql;

use SimpleCrud\Queries\Query;
use SimpleCrud\Table;
use PDO;

/**
 * Manages a database select count query in Mysql databases.
 */
class Sum extends Query
{
    use ExtendedSelectionTrait;

    protected $field;

    /**
     * Set the field name to count.
     *
     * @param string $field
     *
     * @return self
     */
    public function field($field)
    {
        $this->field = $field;

        return $this;
    }

    /**
     * Run the query and return the value.
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
     * Build and return the query.
     *
     * @return string
     */
    public function __toString()
    {
        $query = "SELECT SUM(`{$this->field}`) FROM `{$this->table->getName()}`";

        $query .= $this->fromToString();
        $query .= $this->whereToString();
        $query .= $this->limitToString();

        return $query;
    }
}
