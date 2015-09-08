<?php
namespace SimpleCrud\Queries;

use SimpleCrud\Entity;
use SimpleCrud\QueryInterface;

/**
 * Base class used by all queries
 */
abstract class BaseQuery implements QueryInterface
{
    protected $entity;

    /**
     * Constructor
     *
     * @param Entity $entity
     */
    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
    }
}
