<?php

namespace SimpleCrud\Fields;

final class Json extends Field
{
    protected $config = [
        'assoc' => true,
    ];

    public function format($value)
    {
        return empty($value) ? [] : json_decode($value, $this->config['assoc']);
    }

    protected function formatToDatabase($value)
    {
        if (!is_string($value)) {
            return json_encode($value);
        }

        return $value;
    }
}
