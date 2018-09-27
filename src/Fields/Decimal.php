<?php

namespace SimpleCrud\Fields;

final class Decimal extends Field
{
    public function rowValue($value, array $data = [])
    {
        return static::normalize($value);
    }

    public function databaseValue($value, array $data = [])
    {
        return static::normalize($value);
    }

    protected static function normalize($number): ?float
    {
        return strlen($number) ? (float) $number : null;
    }
}
