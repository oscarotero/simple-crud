<?php

namespace SimpleCrud;

use PDOStatement;

/**
 * Interface used by all queries.
 */
interface QueryInterface
{
    /**
     * Constructor.
     *
     * @param Entity $entity
     */
    public function __construct(Entity $entity);

    /**
     * Run the query and return the statement object.
     *
     * @return PDOStatement
     */
    public function __invoke();

    /**
     * Build and return the query.
     *
     * @return string
     */
    public function __toString();

    /**
     * Run the query and return the result.
     *
     * @return mixed
     */
    public function run();
}
