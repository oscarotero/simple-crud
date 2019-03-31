<?php
declare(strict_types = 1);

namespace SimpleCrud\Query;

final class Count implements QueryInterface
{
    const AGGREGATION_FUNCTION = 'COUNT';

    use Traits\Aggregation;
}
