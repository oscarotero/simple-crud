<?php

namespace SimpleCrud\Fields;

class Set extends Field
{
    public function databaseValue($value, array $data = [])
    {
        if (is_array($value)) {
            return implode(',', $value);
        }

        return $value;
    }

    public function rowValue($value, array $data = [])
    {
        return explode(',', $value);
    }
}
