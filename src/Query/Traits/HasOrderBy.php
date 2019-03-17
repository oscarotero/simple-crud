<?php
declare(strict_types = 1);

namespace SimpleCrud\Query\Traits;

trait HasOrderBy
{
    public function orderBy(string $field, $direction = ''): self
    {
        $expr = trim(sprintf('%s %s', $field, $direction));
        $this->query->orderBy($expr);

        return $this;
    }
}
