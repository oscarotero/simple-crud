<?php
namespace SimpleCrud;

use SimpleCrud\Fieds\FieldInterface;

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

    public function setAdapter(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * Creates a new instance of an Entity.
     *
     * @param string $name
     *
     * @return Entity
     */
    public function create($name)
    {
        $class = $this->entityNamespace.ucfirst($name);

        if (!class_exists($class)) {
            if ($this->autocreate) {
                if ($this->tables === null) {
                    $this->tables = $this->adapter->getTables();
                }

                if (!in_array($name, $this->tables)) {
                    return false;
                }
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

        $entity->rowClass = class_exists("{$class}Row") ? "{$class}Row" : 'SimpleCrud\\Row';
        $entity->rowCollectionClass = class_exists("{$class}RowCollection") ? "{$class}RowCollection" : 'SimpleCrud\\RowCollection';

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
}
