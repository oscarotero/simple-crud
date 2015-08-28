<?php
namespace SimpleCrud;

/**
 * Base class used by Row and RowCollection
 */
abstract class BaseRow implements RowInterface
{
    protected $entity;
    protected $db;
    protected $functions = [];

    /**
     * Constructor
     * 
     * @param Entity $entity
     */
    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
        $this->db = $entity->getDb();
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
    public function getDb()
    {
        return $this->db;
    }

    /**
     * @see RowInterface
     *
     * {@inheritdoc}
     */
    public function getAttribute($name)
    {
        return $this->db->getAttribute($name);
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
     * Creates and return a Select query related with this entity
     *
     * @param string      $entity
     *
     * @return QueryInterface
     */
    public function select($entity)
    {
        return $this->db->$entity->select()->relatedWith($this);
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
     * Set a custom function
     * 
     * {@inheritdoc}
     * 
     * @return self
     */
    public function setCustomFunction($name, callable $function)
    {
        $this->functions[$name] = $function;

        return $this;
    }

    /**
     * Magic method to execute custom method defined in the entity class
     *
     * @param string $name
     */
    public function __call($name, $arguments)
    {
        if (isset($this->functions[$name])) {
            array_unshift($arguments, $this);

            return call_user_func_array($this->functions[$name], $arguments);
        }
    }
}
