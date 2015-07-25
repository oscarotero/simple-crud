<?php
namespace SimpleCrud\Queries\Sqlite;

use SimpleCrud\Queries\Mysql;
use PDO;

/**
 * Manages a database select count query in Mysql databases
 */
trait CompiledOptionsTrait
{
    protected static $options;

    /**
     * Check whether the sqlite has a compiled option
     * 
     * @param string $name
     * 
     * @return boolean
     */
    public function hasCompiledOption($name)
    {
        if (self::$options === null) {
            self::$options = $this->entity->getDb()->execute('pragma compile_options')->fetchAll(PDO::FETCH_COLUMN);
        }

        return in_array($name, self::$options);
    }
}
