<?php

namespace SimpleCrud\Queries\Sqlite;

use SimpleCrud\Queries\Mysql\Insert as BaseInsert;

/**
 * Manages a database insert query in Sqlite databases.
 */
class Insert extends BaseInsert
{
    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        if (empty($this->data) || !$this->duplications) {
            return parent::__toString();
        }

        $fields = array_keys($this->data);

        $query = "INSERT OR REPLACE INTO `{$this->table->getName()}`";
        $query .= ' (`'.implode('`, `', $fields).'`)';
        $query .= ' VALUES (:'.implode(', :', $fields).')';

        return $query;
    }
}
