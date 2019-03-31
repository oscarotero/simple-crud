<?php
declare(strict_types = 1);

namespace SimpleCrud\Fields;

final class Decimal extends Field
{
    public function format($number): ?float
    {
        if ($number === null) {
            return $number;
        }

        return strlen($number) ? (float) $number : null;
    }
}
