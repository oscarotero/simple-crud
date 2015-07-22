<?php
namespace SimpleCrud\Query;

use SimpleCrud\Entity;

/**
 * Abstract class extended by the rest of the query classes
 */
abstract class Query
{
    protected $entity;

    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
    }
}
