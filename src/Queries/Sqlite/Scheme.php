<?php

namespace SimpleCrud\Queries\Sqlite;

use SimpleCrud\SimpleCrud;
use PDO;

/**
 * Class to retrieve info from a sqlite database.
 */
class Scheme
{
    /**
     * Build and return the database scheme.
     *
     * @param SimpleCrud $db
     *
     * @return array
     */
    public static function get(SimpleCrud $db)
    {
        $scheme = [];
        $tables = $db->execute('SELECT name FROM sqlite_master WHERE type="table" OR type="view"')->fetchAll(PDO::FETCH_COLUMN, 0);

        foreach ($tables as $table) {
            $scheme[$table] = self::getFields($db, $table);
        }

        return $scheme;


        return ;
    }

    /**
     * Build and return the fields of a table.
     *
     * @param SimpleCrud $db
     * @param string     $table
     *
     * @return array
     */
    protected static function getFields(SimpleCrud $db, $table)
    {
        $result = $db->execute("pragma table_info(`{$table}`)")->fetchAll(PDO::FETCH_ASSOC);
        $fields = [];

        foreach ($result as $field) {
            $name = $field['name'];
            
            $fields[$name] = [
                'type' => strtolower($field['type']),
                'null' => ($field['notnull'] !== '1'),
                'default' => $field['dflt_value'],
                'unsigned' => null,
                'length' => null,
                'values' => null,
            ];
        }

        return $fields;
    }
}
