<?php

namespace SimpleCrud\Fields;

final class Set extends Field
{
    public function format($value)
    {
        return explode(',', $value);
    }

    protected function formatToDatabase($value)
    {
        if (is_array($value)) {
            return implode(',', $value);
        }

        return $value;
    }
}
