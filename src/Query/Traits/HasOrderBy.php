<?php
declare(strict_types = 1);

namespace SimpleCrud\Query\Traits;

use InvalidArgumentException;
use RuntimeException;
use SimpleCrud\Row;
use SimpleCrud\RowCollection;
use SimpleCrud\Table;

trait HasOrderBy
{
    public function orderBy(string ...$expr): self
    {
        $this->query->orderBy(...$expr);

        return $this;
    }
}
