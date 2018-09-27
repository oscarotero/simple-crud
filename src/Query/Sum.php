<?php
declare(strict_types = 1);

namespace SimpleCrud\Query;

final class Sum implements QueryInterface
{
    const AGGREGATION_FUNCTION = 'SUM';

    use Traits\AggregationTrait;
}
