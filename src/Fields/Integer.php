<?php
declare(strict_types = 1);

namespace SimpleCrud\Fields;

final class Integer extends Field
{
    public static function getFactory(): FieldFactory
    {
        return new FieldFactory(
            self::class,
            ['bigint', 'int', 'mediumint', 'smallint', 'tinyint', 'year'],
            ['id', '/_id$/']
        );
    }

    public function format($value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        return strlen((string) $value) ? (int) $value : null;
    }
}
