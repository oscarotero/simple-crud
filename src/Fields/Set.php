<?php

namespace SimpleCrud\Fields;

/**
 * Normalices "set" values, to work with them as arrays.
 */
class Set extends Field
{
    /**
     * {@inheritdoc}
     */
    public function dataToDatabase($data)
    {
        if (is_array($data)) {
            return implode(',', $data);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function dataFromDatabase($data)
    {
        return explode(',', $data);
    }
}
