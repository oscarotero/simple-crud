<?php
namespace SimpleCrud;

use SimpleCrud\SimpleCrudException;
use SimpleCrud\Queries\QueryInterface;
use Interop\Container\ContainerInterface;
use PDO;

/**
 * Class to create instances of queries.
 */
class QueryFactory implements ContainerInterface
{
    protected $entity;
    protected $namespaces = ['SimpleCrud\\Queries\\'];

    public function setEntity(Entity $entity)
    {
        $this->entity = $entity;
        $this->addNamespace('SimpleCrud\\Queries\\'.ucfirst($entity->getAttribute(PDO::ATTR_DRIVER_NAME)).'\\');
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
     * Check whether or not an Entity is instantiable.
     *
     * @param string $name
     *
     * @return boolean
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
     * Creates a new instance of a Field
     *
     * @param string $name
     *
     * @return FieldInterface
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
            throw new ContainerException("Error getting the '{$name}' query", 0, $exception);
        }

        throw new NotFoundException("The query '{$name}' is not found");
    }
}
