<?php

namespace SimpleCrud\Fields;

class Serializable extends Field
{
    protected $config = [
        'allowed_classes' => false,
    ];

    public function databaseValue($value, array $data = [])
    {
        return serialize($value);
    }

    public function rowValue($value, array $data = [])
    {
        return @unserialize($value, $this->config) ?: [];
    }
}
