<?php

namespace SimpleCrud\Fields;

final class Serializable extends Field
{
    protected $config = [
        'allowed_classes' => false,
    ];

    public function format($value)
    {
        return @unserialize($value, $this->config) ?: [];
    }

    protected function formatToDatabase($value, array $data = [])
    {
        return serialize($value);
    }
}
