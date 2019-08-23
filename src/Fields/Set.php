<?php
declare(strict_types = 1);

namespace SimpleCrud\Fields;

final class Set extends Field
{
    public static function getFactory(): FieldFactory
    {
        return new FieldFactory(
            self::class,
            ['set']
        );
    }

    public function format($value): array
    {
        return explode(',', $value);
    }

    protected function formatToDatabase($value): ?string
    {
        if (is_array($value)) {
            return implode(',', $value);
        }

        return $value;
    }
}
