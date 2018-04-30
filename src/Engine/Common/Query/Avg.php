<?php
declare(strict_types = 1);

namespace SimpleCrud\Engine\Common\Query;

use SimpleCrud\Engine\QueryInterface;

abstract class Avg implements QueryInterface
{
    const AGGREGATION_FUNCTION = 'AVG';

    use AggregationTrait;
}
