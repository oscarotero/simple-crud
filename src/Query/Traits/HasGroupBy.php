<?php
declare(strict_types = 1);

namespace SimpleCrud\Query\Traits;

trait HasGroupBy
{
    public function groupBy(string $expr): self
    {
        $this->query->groupBy(...$expr);

        return $this;
    }
}
