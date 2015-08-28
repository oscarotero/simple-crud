<?php
namespace SimpleCrud;

use SimpleCrud\Fieds\FieldInterface;
use Interop\Container\ContainerInterface;

use SimpleCrud\Exceptions\ContainerException;
use SimpleCrud\Exceptions\NotFoundException;
use Exception;

/**
 * Class to create instances of fields.
 */
class FieldFactory implements ContainerInterface
{
    protected $entity;
    protected $namespaces = ['SimpleCrud\\Fields\\'];
    protected $defaultField = 'SimpleCrud\\Fields\\Field';

    public function setEntity(Entity $entity)
    {
        $this->entity = $entity;
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

            return new $this->defaultField($this->entity);
        } catch (Exception $exception) {
            throw new ContainerException("Error getting the '{$name}' field", 0, $exception);
        }
    }
}
