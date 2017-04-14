<?php

namespace SimpleCrud\Queries\Mysql;

use SimpleCrud\Queries\Query;
use SimpleCrud\Table;

/**
 * Manages a database delete query in Mysql databases.
 */
class Delete extends Query
{
    use SelectionTrait;

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        return $this->table->getDatabase()->execute((string) $this, $this->marks);
    }

    /**
     * Build and return the query.
     *
     * @return string
     */
    public function __toString()
    {
        $query = "DELETE FROM `{$this->table->getName()}`";

        $query .= $this->whereToString();
        $query .= $this->limitToString();

        return $query;
    }
}
