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
    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
    }

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
