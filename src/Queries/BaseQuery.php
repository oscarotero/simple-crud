<?php

namespace SimpleCrud\Queries;

use SimpleCrud\Entity;
use SimpleCrud\QueryInterface;

/**
 * Base class used by all queries.
 */
abstract class BaseQuery implements QueryInterface
{
    protected $entity;

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
     * Executes the query and returns true if it's ok
     * 
     * @return bool
     */
    public function run()
    {
        $this->__invoke();

        return true;
    }
}
