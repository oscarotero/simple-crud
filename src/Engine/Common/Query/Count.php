<?php
declare(strict_types = 1);

namespace SimpleCrud\Engine\Common\Query;

use SimpleCrud\Engine\QueryInterface;

abstract class Count implements QueryInterface
{
    const AGGREGATION_FUNCTION = 'COUNT';

    use AggregationTrait;
}
