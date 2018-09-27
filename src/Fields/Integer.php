<?php

namespace SimpleCrud\Fields;

final class Integer extends Field
{
    public function format($value): ?int
    {
        return strlen($value) ? (int) $value : null;
    }
}
