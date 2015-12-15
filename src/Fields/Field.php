<?php

namespace SimpleCrud\Fields;

use SimpleCrud\FieldInterface;

/**
 * Generic field. The data won't be converted.
 */
class Field implements FieldInterface
{
    protected $config = [];

    /**
     * {@inheritdoc}
     */
    public function setConfig(array $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return $this->config;
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
