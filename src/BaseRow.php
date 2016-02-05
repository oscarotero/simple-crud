<?php

namespace SimpleCrud;

/**
 * Base class used by Row and RowCollection.
 *
 * @property mixed $id
 */
abstract class BaseRow implements RowInterface
{
    protected $entity;
    protected $methods = [];
    protected $properties = [];

    /**
     * Constructor.
     *
     * @param Entity $entity
     */
    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
    }

    /**
     * @see RowInterface
     *
     * {@inheritdoc}
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @see RowInterface
     *
     * {@inheritdoc}
     */
    public function getAttribute($name)
    {
        return $this->entity->getDb()->getAttribute($name);
    }

    /**
     * @see RowInterface
     *
     * {@inheritdoc}
     *
     * @return self
     */
    public function registerMethod($name, callable $callable)
    {
        $this->methods[$name] = $callable;

        return $this;
    }

    /**
     * @see RowInterface
     *
     * {@inheritdoc}
     *
     * @return self
     */
    public function registerProperty($name, callable $callable)
    {
        $this->properties[$name] = $callable;

        return $this;
    }

    /**
     * @see JsonSerializable
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Deletes the row(s) in the database.
     *
     * @return self
     */
    public function delete()
    {
        $id = $this->id;

        if (!empty($id)) {
            $this->entity->delete()
                ->byId($id)
                ->run();

            $this->id = null;
        }

        return $this;
    }

    /**
     * Magic method to execute custom methods defined in the entity class.
     *
     * @param string $name
     */
    public function __call($name, $arguments)
    {
        if (isset($this->methods[$name])) {
            array_unshift($arguments, $this);

            return call_user_func_array($this->methods[$name], $arguments);
        }

        //Queries of related entities
        switch ($this->entity->getRelation($name)) {
            case Entity::RELATION_HAS_ONE:
                $entity = $this->entity->getDb()->get($name);
                return $entity->select()->one()->relatedWith($this);

            case Entity::RELATION_HAS_MANY:
            case Entity::RELATION_HAS_BRIDGE:
                $entity = $this->entity->getDb()->get($name);
                return $entity->select()->relatedWith($this);
        }

        throw new \BadMethodCallException(sprintf('Call to undefined method %s', $name));
    }
}
