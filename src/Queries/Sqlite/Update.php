<?php
namespace SimpleCrud\Queries\Sqlite;

use SimpleCrud\Queries\Mysql\Update as BaseUpdate;

/**
 * Manages a database update query in Sqlite databases
 */
class Update extends BaseUpdate
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
