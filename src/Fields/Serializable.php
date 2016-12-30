<?php

namespace SimpleCrud\Fields;

/**
 * To serialize values before save.
 */
class Serializable extends Field
{
    protected $config = [
        'allowed_classes' => false
    ];

    /**
     * {@inheritdoc}
     */
    public function dataToDatabase($data)
    {
        return serialize($data);
    }

    /**
     * {@inheritdoc}
     */
    public function dataFromDatabase($data)
    {
        return @unserialize($data, $this->config) ?: [];
    }
}
