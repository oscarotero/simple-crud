<?php
declare(strict_types = 1);

namespace SimpleCrud\Query\Traits;

use InvalidArgumentException;
use RuntimeException;
use SimpleCrud\Row;
use SimpleCrud\RowCollection;
use SimpleCrud\Table;

trait HasGroupBy
{
    public function groupBy(string $expr): self
    {
        $this->query->groupBy(...$expr);

        return $this;
    }
}
