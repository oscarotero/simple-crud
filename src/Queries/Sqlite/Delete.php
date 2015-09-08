<?php
namespace SimpleCrud\Queries\Sqlite;

use SimpleCrud\Queries\Mysql\Delete as BaseDelete;

/**
 * Manages a database delete query in Sqlite databases
 */
class Delete extends BaseDelete
{
    use CompiledOptionsTrait;

    /**
     * Adds a LIMIT clause
     *
     * @param integer $limit
     * @param boolean $force
     *
     * @return self
     */
    public function limit($limit, $force = false)
    {
        if ($force || $this->hasCompiledOption('ENABLE_UPDATE_DELETE_LIMIT')) {
            $this->limit = $limit;
        }

        return $this;
    }

    /**
     * Adds an offset to the LIMIT clause
     *
     * @param integer $offset
     * @param boolean $force
     *
     * @return self
     */
    public function offset($offset, $force = false)
    {
        if ($force || $this->hasCompiledOption('ENABLE_UPDATE_DELETE_LIMIT')) {
            $this->offset = $offset;
        }

        return $this;
    }
}
