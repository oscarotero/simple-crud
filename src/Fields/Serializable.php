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
        if (!is_string($value)) {
            return null;
        }

        return @unserialize($value, $this->config['unserialize']) ?: [];
    }

    protected function formatToDatabase($value): ?string
    {
        if ($value === null) {
            return $value;
        }

        return serialize($value);
    }
}
