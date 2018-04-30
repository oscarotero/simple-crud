<?php

namespace SimpleCrud\Fields;

class Datetime extends Field
{
    protected $format = 'Y-m-d H:i:s';

    public function databaseValue($value, array $data = [])
    {
        if (empty($value)) {
            return;
        }

        if (is_string($value)) {
            return date($this->format, strtotime($value));
        }

        if ($value instanceof \Datetime) {
            return $value->format($this->format);
        }
    }

    public function rowValue($value, array $data = [])
    {
        return $value ? new \Datetime($value) : null;
    }
}
