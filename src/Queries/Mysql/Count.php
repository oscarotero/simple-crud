<?php

namespace SimpleCrud\Queries\Mysql;

use SimpleCrud\Queries\Query;
use SimpleCrud\Table;

/**
 * Manages a database select count query.
 */
class Count extends Query
{
    use AggregationTrait;
    const AGGREGATION_FUNCTION = 'COUNT';

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
    public function __toString()
    {
        $query = "SELECT ".self::AGGREGATION_FUNCTION."(*) FROM `{$this->table->getName()}`";

        $query .= $this->fromToString();
        $query .= $this->whereToString();
        $query .= $this->limitToString();

        return $query;
    }
}
