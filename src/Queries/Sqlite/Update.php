<?php
namespace SimpleCrud\Queries\Sqlite;

use SimpleCrud\Queries\Mysql;

/**
 * Manages a database update query in Mysql databases
 */
class Update extends Mysql\Update
{
    use CompiledOptionsTrait;

    /**
     * Adds a LIMIT clause
     * 
     * @param integer $limit
     * 
     * @return self
     */
    public function limit($limit)
    {
        if ($this->hasCompiledOption('ENABLE_UPDATE_DELETE_LIMIT')) {
            $this->limit = $limit;
        }

        return $this;
    }

    /**
     * Adds an offset to the LIMIT clause
     * 
     * @param integer $offset
     * 
     * @return self
     */
    public function offset($offset)
    {
        if ($this->hasCompiledOption('ENABLE_UPDATE_DELETE_LIMIT')) {
            $this->offset = $offset;
        }

        return $this;
    }
}
