<?php

namespace SimpleCrud\Fields;

class Decimal extends Integer
{
    protected static function normalize($number): ?float
    {
        return strlen($number) ? (float) $number : null;
    }
}
