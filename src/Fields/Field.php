<?php

namespace SimpleCrud\Fields;

use SimpleCrud\FieldInterface;
use SimpleCrud\Entity;

/**
 * Generic field. The data won't be converted.
 */
class Field implements FieldInterface
{
    protected $entity;
    protected $config = [];

    /**
     * {@inheritdoc}
     */
    public function __construct(Entity $entity, array $config)
    {
        $this->entity = $entity;
        $this->config += $config;
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

    /**
     * {@inheritdoc}
     */
    public function getConfig($name)
    {
        return isset($this->config[$name]) ? $this->config[$name] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function setConfig($name, $value)
    {
        $this->config[$name] = $value;
    }
}
