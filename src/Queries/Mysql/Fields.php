<?php
namespace SimpleCrud\Queries\Mysql;

use SimpleCrud\Queries\QueryInterface;
use SimpleCrud\RowCollection;
use SimpleCrud\Row;
use SimpleCrud\Entity;
use SimpleCrud\SimpleCrudException;
use PDOStatement;
use PDO;

/**
 * Manages a database query to get the fields names in Mysql databases
 */
class Fields implements QueryInterface
{
    protected $entity;

    /**
     * @see QueryInterface
     * 
     * {@inheritdoc}
     */
    public static function getInstance(Entity $entity)
    {
        return new static($entity);
    }

    /**
     * @see QueryInterface
     * 
     * $entity->fields()
     * 
     * {@inheritdoc}
     */
    public static function execute(Entity $entity, array $args)
    {
        return self::getInstance($entity)->get();
    }


    /**
     * Constructor
     * 
     * @param Entity $entity
     */
    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
    }

    /**
     * Run the query and return all values
     * 
     * @return PDOStatement
     */
    public function run()
    {
        return $this->entity->getDb()->execute((string) $this);
    }

    /**
     * Run the query and return the fields
     * 
     * @return array
     */
    public function get()
    {
        $result = [];

        foreach ($this->run()->fetchAll() as $field) {
            preg_match('#^(\w+)#', $field['Type'], $matches);

            $result[$field['Field']] = $matches[1];
        }

        return $result;
    }

    /**
     * Build and return the query
     * 
     * @return string
     */
    public function __toString()
    {
        return "DESCRIBE `{$this->entity->table}`";
    }
}
