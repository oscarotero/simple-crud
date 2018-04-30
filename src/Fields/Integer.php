<?php

namespace SimpleCrud\Fields;

class Integer extends Field
{
    public function rowValue($value, array $data = [])
    {
        return static::normalize($value);
    }

    public function databaseValue($value, array $data = [])
    {
        return static::normalize($value);
    }

    protected static function normalize($number): ?int
    {
        return strlen($number) ? (int) $number : null;
    }
}
