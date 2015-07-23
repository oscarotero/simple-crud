<?php
namespace SimpleCrud\Fields;

/**
 * Generic field. The data won't be converted.
 */
class Field implements FieldInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getInstance()
    {
        return new static();
    }

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
