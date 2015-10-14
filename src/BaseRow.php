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
     * Creates and return a Select query related with this entity.
     *
     * @param string $entity
     *
     * @return QueryInterface
     */
    public function select($entity)
    {
        $db = $this->entity->getDb();

        return $db->$entity->select()->relatedWith($this);
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
     * Magic method to execute custom method defined in the entity class.
     *
     * @param string $name
     */
    public function __call($name, $arguments)
    {
        if (isset($this->methods[$name])) {
            array_unshift($arguments, $this);

            return call_user_func_array($this->methods[$name], $arguments);
        }
    }
}
