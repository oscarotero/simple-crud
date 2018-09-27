<?php

namespace SimpleCrud\Fields;

final class Boolean extends Field
{
    public function databaseValue($value, array $data = [])
    {
        return (int) filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function rowValue($value, array $data = [])
    {
        return (bool) $data;
    }
}
