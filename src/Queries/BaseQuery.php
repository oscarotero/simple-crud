<?php
namespace SimpleCrud\Queries;

use SimpleCrud\Entity;

/**
 * Base class used by all queries
 */
abstract class BaseQuery implements QueryInterface
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
     * Constructor
     *
     * @param Entity $entity
     */
    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
    }

    /**
     * Magic method to execute Entity's queries
     *
     * @param string $name
     * @param array  $arguments
     */
    public function __call($name, $arguments)
    {
        $fn_name = "query{$name}";

        if (method_exists($this->entity, $fn_name)) {
            array_unshift($arguments, $this);
            call_user_func_array([$this->entity, $fn_name], $arguments);

            return $this;
        }

        throw new \Exception("Not valid function '{$fn_name}' in the entity '{$this->entity->name}'");
    }
}
