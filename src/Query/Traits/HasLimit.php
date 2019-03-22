<?php
declare(strict_types = 1);

namespace SimpleCrud\Query\Traits;

trait HasLimit
{
    public function limit(int $limit): self
    {
        $this->query->limit($limit);

        return $this;
    }

    public function offset(int $offset): self
    {
        $this->query->offset($offset);

        return $this;
    }
}
