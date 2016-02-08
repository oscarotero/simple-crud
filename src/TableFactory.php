<?php

namespace SimpleCrud;

/**
 * Class to create instances of tables.
 */
class TableFactory implements TableFactoryInterface
{
    protected $namespaces = [];
    protected $default;

    /**
     * Add a namespace for the entities classes.
     *
     * @param string $namespace
     *
     * @return self
     */
    public function addNamespace($namespace)
    {
        array_unshift($this->namespaces, $namespace);

        return $this;
    }

    /**
     * Set whether the entities are autocreated or not.
     *
     * @param string $default Default class used by the tables
     *
     * @return self
     */
    public function setAutocreate($default = 'SimpleCrud\\Table')
    {
        $this->default = $default;

        return $this;
    }

    /**
     * @see TableFactoryInterface
     *
     * {@inheritdoc}
     */
    public function get(SimpleCrud $db, $name)
    {
        try {
            $className = ucfirst($name);

            foreach ($this->namespaces as $namespace) {
                $class = $namespace.$className;

                if (class_exists($class)) {
                    return new $class($db, $name);
                }
            }

            if ($this->default) {
                $class = $this->default;

                return new $class($db, $name);
            }
        } catch (\Exception $exception) {
            throw new SimpleCrudException("Error getting the '{$name}' table", 0, $exception);
        }

        throw new SimpleCrudException("Table '{$name}' not found");
    }
}
