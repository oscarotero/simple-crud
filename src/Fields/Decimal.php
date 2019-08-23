<?php
declare(strict_types = 1);

namespace SimpleCrud\Fields;

final class Decimal extends Field
{
    public static function getFactory(): FieldFactory
    {
        return new FieldFactory(
            self::class,
            ['decimal', 'float', 'real']
        );
    }

    public function format($number): ?float
    {
        if ($number === null) {
            return $number;
        }

        return strlen($number) ? (float) $number : null;
    }
}
