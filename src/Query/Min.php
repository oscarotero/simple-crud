<?php
declare(strict_types = 1);

namespace SimpleCrud\Query;

final class Min implements QueryInterface
{
    const AGGREGATION_FUNCTION = 'MIN';

    use Traits\Aggregation;
    use Traits\HasWhere;
    use Traits\HasLimit;
}
