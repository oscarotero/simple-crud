<?php
namespace SimpleCrud;

use SimpleCrud\Fieds\FieldInterface;
use PDO;

/**
 * Class to create instances.
 */
class Factory
{
    protected $entities;
    protected $queries;
    protected $fields;
    protected $autocreate;
    protected $tables;
    protected $db;
    protected $default_entity = 'SimpleCrud\\Entity';
    protected $default_queries = 'SimpleCrud\\Queries\\';
    protected $default_fields = 'SimpleCrud\\Fields\\';

    public function init(SimpleCrud $db)
    {
        $this->db = $db;
        $this->default_queries .= ucfirst($db->getAttribute(PDO::ATTR_DRIVER_NAME)).'\\';
    }

    /**
     * Set the namespace for the entities classes
     * 
     * @param string $namespace
     * 
     * @return self
     */
    public function entities($namespace)
    {
        $this->entities = $namespace;

        return $this;
    }

    /**
     * Set the namespace for the queries classes
     * 
     * @param string $namespace
     * 
     * @return self
     */
    public function queries($namespace)
    {
        $this->queries = $namespace;

        return $this;
    }

    /**
     * Set the namespace for the fields classes
     * 
     * @param string $namespace
     * 
     * @return self
     */
    public function fields($namespace)
    {
        $this->fields = $namespace;

        return $this;
    }

    /**
     * Set whether the entities are autocreated or not
     * 
     * @param boolean $autocreate
     * 
     * @return self
     */
    public function autocreate($autocreate = true)
    {
        $this->autocreate = $autocreate;

        return $this;
    }

    /**
     * Check whether or not an Entity is instantiable.
     *
     * @param string $name
     *
     * @return boolean
     */
    public function hasEntity($name)
    {
        return in_array($name, $this->getTables()) || class_exists($this->entities.ucfirst($name));
    }

    /**
     * Creates a new instance of an Entity.
     *
     * @param string $name
     *
     * @return Entity|null
     */
    public function getEntity($name)
    {
        $class = $this->entities.ucfirst($name);

        if (class_exists($class)) {
            return $class::getInstance($name, $this->db);
        }

        $class = $this->default_entity;

        if ($this->autocreate && in_array($name, $this->getTables())) {
            return $class::getInstance($name, $this->db);
        }
    }

    /**
     * Creates a new instance of a Field
     *
     * @param Entity $entity
     * @param string $name
     *
     * @return FieldInterface
     */
    public function getField(Entity $entity, $name)
    {
        $name = ucfirst($name);
        $class = $this->fields.$name;

        if (class_exists($class)) {
            return $class::getInstance($entity);
        }

        $class = $this->default_fields.$name;

        if (class_exists($class)) {
            return $class::getInstance($entity);
        }

        return Fields\Field::getInstance();
    }


    /**
     * Creates a new instance of a Query for a entity
     *
     * @param Entity $entity
     * @param string $name
     *
     * @return FieldInterface|null
     */
    public function getQuery(Entity $entity, $name)
    {
        $name = ucfirst($name);
        $class = $this->queries.$name;

        if (class_exists($class)) {
            return $class::getInstance($entity);
        }

        $class = $this->default_queries.$name;

        if (class_exists($class)) {
            return $class::getInstance($entity);
        }
    }


    /**
     * Returns all tables in the database
     * 
     * @return array
     */
    private function getTables()
    {
        if ($this->tables === null) {
            $class = $this->default_queries.'Tables';
            $this->tables = $class::getInstance($this->db)->get();
        }

        return $this->tables;
    }
}
