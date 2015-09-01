<?php
namespace SimpleCrud\Fields;

use SimpleCrud\FieldInterface;

/**
 * Generic field. The data won't be converted.
 */
class Field implements FieldInterface
{
    /**
     * {@inheritdoc}
     */
    public function dataToDatabase($data)
    {
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function dataFromDatabase($data)
    {
        return $data;
    }
}
