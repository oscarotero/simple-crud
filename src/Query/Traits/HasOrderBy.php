<?php
declare(strict_types = 1);

namespace SimpleCrud\Query\Traits;

trait HasOrderBy
{
    public function orderBy(string ...$expr): self
    {
        $this->query->orderBy(...$expr);

        return $this;
    }
}
