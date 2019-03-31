<?php

namespace SimpleCrud\Fields;

use Atlas\Query\Insert;
use Atlas\Query\Update;

final class Boolean extends Field
{
    public function insert(Insert $query, $value)
    {
        $query->column($this->info['name'], (int) $this->format($value));
    }

    public function update(Update $query, $value)
    {
        $query->column($this->info['name'], (int) $this->format($value));
    }

    public function format($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
