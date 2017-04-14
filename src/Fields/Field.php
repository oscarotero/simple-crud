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
     * @param Table $table
     * @param string $name
     */
    public function __construct(Table $table, $name)
    {
        $this->table = $table;
        $this->name = $name;
    }

    /**
     * Converts the data to save in the database
     *
     * @param mixed $data
     *
     * @return mixed
     */
    public function dataToDatabase($data)
    {
        return $data;
    }

    /**
     * Converts the data to be used
     *
     * @param mixed $data
     *
     * @return mixed
     */
    public function dataFromDatabase($data)
    {
        return $data;
    }

    /**
     * Returns the field scheme
     *
     * @return array
     */
    public function getScheme()
    {
        return $this->table->getScheme()['fields'][$this->name];
    }

    /**
     * Returns a config value
     *
     * @return mixed
     */
    public function getConfig($name)
    {
        return isset($this->config[$name]) ? $this->config[$name] : null;
    }

    /**
     * Edit a config value
     *
     * @param string $name
     * @param mixed $value
     *
     * @return self
     */
    public function setConfig($name, $value)
    {
        $this->config[$name] = $value;

        return $this;
    }
}
