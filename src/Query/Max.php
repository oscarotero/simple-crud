<?php
declare(strict_types = 1);

namespace SimpleCrud\Query;

final class Max implements QueryInterface
{
    const AGGREGATION_FUNCTION = 'MAX';

    use Traits\AggregationTrait;
}
