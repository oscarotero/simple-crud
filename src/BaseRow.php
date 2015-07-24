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
        return $this->entity->getAdapter();
    }

    /**
     * @see RowInterface
     * 
     * {@inheritdoc}
     */
    public function getAttribute($name)
    {
        return $this->getAdapter()->getAttribute($name);
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
     * Returns related stuff
     * 
     * @param Entity $entity
     * @param string|null $where
     * @param string|null $marks
     * @param string|null $orderBy
     * @param int|null $limit
     *
     * @return Queries\QueryInterface
     */
    protected function relationSelection(Entity $entity, $where = null, $marks = null, $orderBy = null, $limit = null)
    {
        $select = $entity->querySelect()->relatedWith($this);

        if ($where !== null) {
            $select->where($where, $marks);
        }

        if ($orderBy !== null) {
            $select->orderBy($orderBy);
        }

        if ($limit) {
            $select->limit($limit);
        }

        return $select;
    }
}
