<?php

namespace SimpleCrud\Fields;

use SimpleCrud\Table;

/**
 * Generic field.
 */
class Field
{
    protected $table;
    protected $name;
    protected $config = [];

    /**
     * {@inheritdoc}
     */
    public function __construct(Table $table, $name)
    {
        $this->table = $table;
        $this->name = $name;
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
    public function getScheme()
    {
        return $this->table->getScheme()['fields'][$this->name];
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
