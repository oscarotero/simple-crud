<?php

namespace SimpleCrud\Fields;

final class Json extends Field
{
    protected $config = [
        'assoc' => true,
    ];

    public function databaseValue($value, array $data = [])
    {
        if (!is_string($value)) {
            return json_encode($value);
        }

        return $value;
    }

    public function rowValue($vale, array $data = [])
    {
        return empty($value) ? [] : json_decode($value, $this->config['assoc']);
    }
}
