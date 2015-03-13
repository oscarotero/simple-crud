<?php
namespace SimpleCrud;

use SimpleCrud\Fieds\FieldInterface;
use SimpleCrud\Adapters\AdapterInterface;

/**
 * Class to create entity instances.
 */
class EntityFactory
{
    protected $entityNamespace;
    protected $fieldsNamespace;
    protected $autocreate;
    protected $tables;
    protected $adapter;

    public function __construct(array $config = null)
    {
        $this->entityNamespace = isset($config['namespace']) ? $config['namespace'] : '';
        $this->fieldsNamespace = $this->entityNamespace.'Fields\\';
        $this->autocreate = isset($config['autocreate']) ? (bool) $config['autocreate'] : false;
    }

    public function setAdapter(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
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
        return in_array($name, $this->getAvailableTables()) || class_exists($this->entityNamespace.ucfirst($name));
    }

    /**
     * Creates a new instance of an Entity.
     *
     * @param string $name
     *
     * @return Entity|false
     */
    public function get($name)
    {
        $className = ucfirst($name);
        $class = $this->entityNamespace.$className;

        if (!class_exists($class)) {
            if (!in_array($name, $this->getAvailableTables())) {
                return false;
            }

            $class = 'SimpleCrud\\Entity';
        }

        $entity = new $class($this->adapter, $name);

        //Configure the entity
        if (empty($entity->table)) {
            $entity->table = $name;
        }

        if (empty($entity->foreignKey)) {
            $entity->foreignKey = "{$entity->table}_id";
        }

        $entity->rowClass = $this->getCustomClass('Row', $className);
        $entity->rowCollectionClass = $this->getCustomClass('RowCollection', $className);

        //Define fields
        $fields = [];

        if (empty($entity->fields)) {
            foreach ($this->adapter->getFields($entity->table) as $name => $type) {
                $fields[$name] = $this->createField($type);
            }
        } else {
            foreach ($entity->fields as $name => $type) {
                if (is_int($name)) {
                    $fields[$type] = $this->createField('field');
                } else {
                    $fields[$name] = $this->createField($type);
                }
            }
        }

        $entity->fields = $fields;

        //Init callback
        $entity->init();

        return $entity;
    }

    /**
     * Returns all available tables in the database
     */
    private function getAvailableTables()
    {
        if ($this->tables !== null) {
            return $this->tables;
        }

        return $this->tables = $this->autocreate ? $this->adapter->getTables() : [];
    }

    /**
     * Creates a field instance.
     *
     * @param string $type The field type
     *
     * @return FieldInterface The created field
     */
    private function createField($type)
    {
        $class = $this->fieldsNamespace.ucfirst($type);

        if (!class_exists($class)) {
            $class = 'SimpleCrud\\Fields\\'.ucfirst($type);

            if (!class_exists($class)) {
                $class = 'SimpleCrud\\Fields\\Field';
            }
        }

        return new $class();
    }

    /**
     * Get the row or rowcollection class
     * 
     * @param string $class
     * @param string $entityClassName
     */
    private function getCustomClass($class, $entityClassName)
    {
        $className = "{$this->entityNamespace}{$class}s\\{$entityClassName}";

        if (class_exists($className)) {
            return $className;
        }

        $className = "{$this->entityNamespace}{$class}s\\{$class}";

        if (class_exists($className)) {
            return $className;
        }

        return "SimpleCrud\\{$class}";
    }
}
