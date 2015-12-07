<?php

namespace SimpleCrud\Fields;

/**
 * To normalize float values.
 */
class Decimal extends Integer
{
    /**
     * Normalize a float.
     *
     * @param mixed $number
     *
     * @return float|null
     */
    protected static function normalize($number)
    {
        return strlen($number) ? (float) $number : null;
    }
}
