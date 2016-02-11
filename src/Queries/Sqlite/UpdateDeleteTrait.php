<?php

namespace SimpleCrud\Queries\Sqlite;

use SimpleCrud\SimpleCrudException;
use SimpleCrud\Queries\Mysql;
use PDO;

/**
 * Manages a database select count query in Mysql databases.
 *
 * @property \SimpleCrud\Table $table
 */
trait UpdateDeleteTrait
{
    protected static $options;

    /**
     * Adds a LIMIT clause.
     *
     * @param int  $limit
     * @param bool $force
     *
     * @return self
     */
    public function limit($limit, $force = false)
    {
        if (!$this->hasCompiledOption('ENABLE_UPDATE_DELETE_LIMIT')) {
            if (!$force) {
                return $this;
            }

            throw new SimpleCrudException('Unable to add LIMIT because ENABLE_UPDATE_DELETE_LIMIT compiled option is disabled');
        }

        $this->limit = $limit;

        return $this;
    }

    /**
     * Adds an offset to the LIMIT clause.
     *
     * @param int $offset
     *
     * @return self
     */
    public function offset($offset)
    {
        if (!$this->hasCompiledOption('ENABLE_UPDATE_DELETE_LIMIT')) {
            throw new SimpleCrudException('Unable to add LIMIT offset because ENABLE_UPDATE_DELETE_LIMIT compiled option is disabled');
        }

        $this->offset = $offset;

        return $this;
    }

    /**
     * Check whether the sqlite has a compiled option.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasCompiledOption($name)
    {
        if (self::$options === null) {
            self::$options = $this->table->getDatabase()->execute('pragma compile_options')->fetchAll(PDO::FETCH_COLUMN);
        }

        return in_array($name, self::$options);
    }
}
