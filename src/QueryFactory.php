<?php
namespace SimpleCrud;

use PDO;

/**
 * Class to create instances of queries.
 */
class QueryFactory implements QueryFactoryInterface
{
    protected $entity;
    protected $namespaces = ['SimpleCrud\\Queries\\'];

    /**
     * @see QueryFactoryInterface
     *
     * {@inheritdoc}
     */
    public function setEntity(Entity $entity)
    {
        $this->entity = $entity;
        $this->addNamespace('SimpleCrud\\Queries\\'.ucfirst($entity->getAttribute(PDO::ATTR_DRIVER_NAME)).'\\');

        return $this;
    }

    /**
     * Set the namespace for the fields classes
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
    public function has($name)
    {
        $name = ucfirst($name);

        foreach ($this->namespaces as $namespace) {
            if (class_exists($namespace.$name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @see QueryFactoryInterface
     *
     * {@inheritdoc}
     */
    public function get($name)
    {
        try {
            $name = ucfirst($name);

            foreach ($this->namespaces as $namespace) {
                $class = $namespace.$name;

                if (class_exists($class)) {
                    return new $class($this->entity);
                }
            }
        } catch (Exception $exception) {
            throw new SimpleCrudException("Error getting the '{$name}' query", 0, $exception);
        }

        throw new SimpleCrudException("Query '{$name}' not found");
    }
}
