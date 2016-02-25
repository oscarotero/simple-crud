<?php

namespace SimpleCrud;

/**
 * Class to create instances of queries.
 */
class QueryFactory implements QueryFactoryInterface
{
    protected $namespaces = [];

    /**
     * Set the namespace for the fields classes.
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
     * @see QueryFactoryInterface
     *
     * {@inheritdoc}
     */
    public function get(Table $table, $name)
    {
        $name = ucfirst($name);

        foreach ($this->namespaces as $namespace) {
            $class = $namespace.$name;

            if (class_exists($class)) {
                return new $class($table);
            }
        }

        throw new SimpleCrudException("Query '{$name}' not found");
    }
}
