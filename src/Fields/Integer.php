<?php
declare(strict_types = 1);

namespace SimpleCrud\Fields;

final class Integer extends Field
{
    public function format($value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        return strlen((string) $value) ? (int) $value : null;
    }
}
