<?php

namespace SimpleCrud\Queries\Mysql;

use SimpleCrud\Queries\BaseQuery;
use SimpleCrud\Queries\SelectionTrait;
use SimpleCrud\Entity;
use PDOStatement;

/**
 * Manages a database delete query in Mysql databases.
 */
class Delete extends BaseQuery
{
    use SelectionTrait;

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        return $this->entity->getDb()->execute((string) $this, $this->marks);
    }

    /**
     * Build and return the query.
     *
     * @return string
     */
    public function __toString()
    {
        $query = "DELETE FROM `{$this->entity->name}`";

        $query .= $this->whereToString();
        $query .= $this->limitToString();

        return $query;
    }
}
