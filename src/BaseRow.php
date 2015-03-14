<?php
namespace SimpleCrud;

/**
 * Base class used by Row and RowCollection
 */
abstract class BaseRow implements RowInterface
{
    private $entity;

    /**
     * Set the row entity
     * 
     * @param Entity $entity
     */
    protected function setEntity(Entity $entity)
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
    public function getAdapter()
    {
        return $this->entity->adapter;
    }
}
