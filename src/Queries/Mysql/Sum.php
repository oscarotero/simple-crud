<?php

namespace SimpleCrud\Queries\Mysql;

use SimpleCrud\Queries\Query;
use SimpleCrud\Table;

/**
 * Manages a database select sum query in Mysql databases.
 */
class Sum extends Query
{
    use AggregationTrait;
    const AGGREGATION_FUNCTION = 'SUM';
}
