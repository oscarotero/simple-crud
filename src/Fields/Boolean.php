<?php

namespace SimpleCrud\Fields;

/**
 * To normalize boolean values.
 */
class Boolean extends Field
{
    /**
     * {@inheritdoc}
     */
    public function dataToDatabase($data)
    {
        return (integer) filter_var($data, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * {@inheritdoc}
     */
    public function dataFromDatabase($data)
    {
        return (bool) $data;
    }
}
