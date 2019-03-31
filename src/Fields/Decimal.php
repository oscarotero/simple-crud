<?php

namespace SimpleCrud\Fields;

final class Decimal extends Field
{
    public function format($number): ?float
    {
        return strlen($number) ? (float) $number : null;
    }
}
