<?php
declare(strict_types = 1);

namespace SimpleCrud\Engine\Common\Query;

use SimpleCrud\Engine\QueryInterface;

abstract class Min implements QueryInterface
{
    const AGGREGATION_FUNCTION = 'MIN';

    use AggregationTrait;
}
