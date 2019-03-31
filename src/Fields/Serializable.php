<?php
declare(strict_types = 1);

namespace SimpleCrud\Fields;

final class Serializable extends Field
{
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
