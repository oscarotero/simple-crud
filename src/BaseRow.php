<?php
namespace SimpleCrud;

/**
 * Base class used by Row and RowCollection
 */
abstract class BaseRow implements RowInterface
{
    protected $entity;
    protected $db;

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
     * Creates and return a Select query
     *
     * @param string      $entity
     * @param string|null $through
     *
     * @return QueryInterface
     */
    public function select($entity, $through = null)
    {
        return $this->db->select($entity)
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
            $this->db->delete($this->entity->name)
                ->byId($id)
                ->run();

            $this->id = null;
        }

        return $this;
    }
}
