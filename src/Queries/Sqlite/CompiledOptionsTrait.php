<?php

namespace SimpleCrud\Queries\Sqlite;

use SimpleCrud\Queries\Mysql;
use PDO;

/**
 * Manages a database select count query in Mysql databases.
 *
 * @property \SimpleCrud\Table $table
 */
trait CompiledOptionsTrait
{
    protected static $options;

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
            self::$options = $this->table->getDb()->execute('pragma compile_options')->fetchAll(PDO::FETCH_COLUMN);
        }

        return in_array($name, self::$options);
    }
}
