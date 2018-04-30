<?php

namespace SimpleCrud\Queries\Sqlite;

use SimpleCrud\Queries\Mysql\Select as BaseSelect;

/**
 * Manages a database select query in Sqlite databases.
 */
class Select extends BaseSelect
{
    /**
     * {@inheritdoc}
     */
    protected static function buildFoundRows()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function getFoundRows()
    {
        $query = $this->table->count();

        $query->marks($this->marks);

        foreach ($this->where as $k => $where) {
            if ($k === 'or') {
                foreach ($where as $condition) {
                    $query->orWhere($condition);
                }
            } else {
                $query->where($where);
            }
        }

        return $query->run();
    }
}
