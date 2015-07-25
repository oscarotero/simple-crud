<?php
namespace SimpleCrud;

use SimpleCrud\Fieds\FieldInterface;
use PDO;

/**
 * Class to create instances.
 */
class Factory
{
    protected $db;
    protected $tables;
    protected $entities;
    protected $queries;
    protected $fields;
    protected $rows;
    protected $rowCollections;
    protected $autocreate;
    protected $default_entity = 'SimpleCrud\\Entity';
    protected $default_fields = 'SimpleCrud\\Fields\\';
    protected $default_field = 'SimpleCrud\\Fields\\Field';
    protected $default_row = 'SimpleCrud\\Row';
    protected $default_rowCollection = 'SimpleCrud\\RowCollection';

    public function init(SimpleCrud $db)
    {
        $this->db = $db;
        $this->queries = 'SimpleCrud\\Queries\\'.ucfirst($db->getAttribute(PDO::ATTR_DRIVER_NAME)).'\\';
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
     * Set the namespace for the Row classes
     *
     * @param string $namespace
     *
     * @return self
     */
    public function rows($namespace)
    {
        $this->rows = $namespace;

        return $this;
    }

    /**
     * Set the namespace for the RowCollection classes
     *
     * @param string $namespace
     *
     * @return self
     */
    public function rowCollections($namespace)
    {
        $this->rowCollections = $namespace;

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
     * Returns a Row class
     *
     * @param string $name
     *
     * @return string|null
     */
    public function getRowClass($name)
    {
        $name = ucfirst($name);

        if ($this->rows !== null) {
            $class = $this->rows.$name;

            if (class_exists($class)) {
                return $class;
            }
        }

        return $this->default_row;
    }

    /**
     * Returns a RowCollection class
     *
     * @param string $name
     *
     * @return string|null
     */
    public function getRowCollectionClass($name)
    {
        $name = ucfirst($name);

        if ($this->rowCollections !== null) {
            $class = $this->rowCollections.$name;

            if (class_exists($class)) {
                return $class;
            }
        }

        return $this->default_rowCollection;
    }

    /**
     * Returns a Field class
     *
     * @param string $name
     *
     * @return string|null
     */
    public function getFieldClass($name)
    {
        $name = ucfirst($name);

        if ($this->fields !== null) {
            $class = $this->fields.$name;

            if (class_exists($class)) {
                return $class;
            }
        }

        $class = $this->default_fields.$name;

        if (class_exists($class)) {
            return $class;
        }

        return $this->default_field;
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
        $class = $this->getFieldClass($name);

        if (!empty($class)) {
            return $class::getInstance($entity);
        }
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
        $class = $this->queries.ucfirst($name);

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
            $class = $this->queries.'DbTables';
            $this->tables = $class::getInstance($this->db)->get();
        }

        return $this->tables;
    }
}
