<?php

namespace SimpleCrud\Queries\Mysql;

use SimpleCrud\Queries\Query;
use SimpleCrud\Table;


/**
 * Manages a database select avg query in Mysql databases.
 */
class Avg extends Query
{
    use AggregationTrait;
    const AGGREGATION_FUNCTION = 'AVG';
    
    /**
     * Run the query and return the value.
     * 
     * @return int
     */
    public function run()
    {
        $result = $this->__invoke()->fetch();
        
        return (float) $result[0];
    }
    
}
