<?php
declare(strict_types = 1);

namespace SimpleCrud\Query;

final class Avg implements QueryInterface
{
    const AGGREGATION_FUNCTION = 'AVG';

    use Traits\Aggregation;
}
