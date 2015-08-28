<?php
namespace SimpleCrud;

use Interop\Container\ContainerInterface;
use SimpleCrud\Exceptions\ContainerException;
use SimpleCrud\Exceptions\NotFoundException;
use Exception;

/**
 * Class to create instances of entities.
 */
class EntityFactory implements ContainerInterface
{
    protected $db;
    protected $tables;
    protected $namespace;
    protected $defaultEntity;

    public function setDb(SimpleCrud $db)
    {
        $this->db = $db;
    }

    /**
     * Set the namespace for the entities classes
     *
     * @param string $namespace
     *
     * @return self
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;

        return $this;
    }

    /**
     * Set whether the entities are autocreated or not
     *
     * @param string $entity Default class used by the entities
     *
     * @return self
     */
    public function setAutocreate($defaultEntity = 'SimpleCrud\\Entity')
    {
        $this->defaultEntity = $defaultEntity;

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
        return ($this->defaultEntity && in_array($name, $this->getTables())) || class_exists($this->namespace.ucfirst($name));
    }

    /**
     * Creates a new instance of an Entity.
     *
     * @param string $name
     * 
     * @throws NotFoundException
     *
     * @return Entity|null
     */
    public function get($name)
    {
        try {
            $class = $this->namespace.ucfirst($name);

            if (class_exists($class)) {
                return new $class($name, $this->db);
            }

            if ($this->defaultEntity && in_array($name, $this->getTables())) {
                $class = $this->defaultEntity;

                return new $class($name, $this->db);
            }
        } catch (Exception $exception) {
            throw new ContainerException("Error getting the '{$name}' entity", 0, $exception);
        }

        throw new NotFoundException("The entity '{$name}' is not found");
    }

    /**
     * Returns all tables in the database
     *
     * @return array
     */
    private function getTables()
    {
        if ($this->tables === null) {
            $this->tables = $this->db->getTables();
        }

        return $this->tables;
    }
}
