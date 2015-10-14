<?php

namespace SimpleCrud\Fields;

/**
 * To normalize integer values.
 */
class Integer extends Field
{
    /**
     * {@inheritdoc}
     */
    public function dataToDatabase($data)
    {
        return static::normalize($data);
    }

    /**
     * {@inheritdoc}
     */
    public function dataFromDatabase($data)
    {
        return static::normalize($data);
    }

    /**
     * Normalize a number.
     *
     * @param mixed $number
     *
     * @return int|null
     */
    protected static function normalize($number)
    {
        return strlen($number) ? (integer) $number : null;
    }
}
