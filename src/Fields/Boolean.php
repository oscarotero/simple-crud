<?php
declare(strict_types = 1);

namespace SimpleCrud\Fields;

final class Boolean extends Field
{
    public function format($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    protected function formatToDatabase($value): int
    {
        return (int) $this->format($value);
    }
}
