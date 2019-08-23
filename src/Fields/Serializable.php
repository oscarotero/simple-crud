<?php
declare(strict_types = 1);

namespace SimpleCrud\Fields;

final class Serializable extends Field
{
    public static function getFactory(): FieldFactory
    {
        return new FieldFactory(self::class);
    }

    protected $config = [
        'unserialize' => ['allowed_classes' => false],
    ];

    public function format($value)
    {
        return @unserialize($value, $this->config['unserialize']) ?: [];
    }

    protected function formatToDatabase($value): string
    {
        return serialize($value);
    }
}
