<?php
declare(strict_types = 1);

namespace SimpleCrud\Fields;

class Datetime extends Field
{
    protected $format = 'Y-m-d H:i:s';

    public static function getFactory(): FieldFactory
    {
        return new FieldFactory(
            self::class,
            ['datetime'],
            ['pubdate', '/[a-z]At$/']
        );
    }

    public function format($value): ?\Datetime
    {
        if ($value instanceof \Datetime) {
            return $value;
        }

        return $value ? new \Datetime($value) : null;
    }

    protected function formatToDatabase($value): ?string
    {
        $value = $this->format($value);

        return empty($value) ? null : $value->format($this->format);
    }
}
