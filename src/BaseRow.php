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
    public function getDb()
    {
        return $this->entity->getDb();
    }

    /**
     * @see RowInterface
     * 
     * {@inheritdoc}
     */
    public function getAttribute($name)
    {
        return $this->getDb()->getAttribute($name);
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
     * Creates and return a Select query
     *
     * @param string $entity
     * @param string|null $through
     *
     * @return QueryInterface
     */
    public function select($entity, $through = null)
    {
        return $this->getDb()->select($entity)
            ->relatedWith($this, $through);
    }

    /**
     * Deletes the row(s) in the database.
     *
     * @return $this
     */
    public function delete()
    {
        $id = $this->id;

        if (!empty($id)) {
            $this->getEntity()->delete()
                ->byId($id)
                ->run();

            $this->id = null;
        }

        return $this;
    }
}
