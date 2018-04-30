<?php

namespace SimpleCrud\Queries\Mysql;

use SimpleCrud\Queries\Query;

/**
 * Manages a database select min query in Mysql databases.
 */
class Min extends Query
{
    use AggregationTrait;
    const AGGREGATION_FUNCTION = 'MIN';
}
