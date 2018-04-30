<?php
declare(strict_types = 1);

namespace SimpleCrud\Engine\Common\Query;

use SimpleCrud\Engine\QueryInterface;

abstract class Max implements QueryInterface
{
    const AGGREGATION_FUNCTION = 'MAX';

    use AggregationTrait;
}
