<?php

namespace SimpleCrud\Queries\Mysql;

use SimpleCrud\Queries\Query;
use SimpleCrud\Table;


/**
 * Manages a database select max query in Mysql databases.
 */
class Max extends Query
{
    use AggregationTrait;
    const AGGREGATION_FUNCTION = 'MAX';
}
