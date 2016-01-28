<?php

namespace SimpleCrud\Queries\Mysql;

use SimpleCrud\SimpleCrud;
use PDO;

/**
 * Class to retrieve info from a mysql database.
 */
class DbInfo
{
    /**
     * Build and return the query.
     *
     * @param SimpleCrud $db
     *
     * @return array
     */
    public static function getTables(SimpleCrud $db)
    {
        return $db->execute('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    /**
     * Build and return the fields of a table.
     *
     * @param SimpleCrud $db
     * @param string     $table
     *
     * @return array
     */
    public static function getFields(SimpleCrud $db, $table)
    {
        $result = $db->execute("DESCRIBE `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
        $fields = [];

        foreach ($result as $field) {
            preg_match('#^(\w+)(\((.+)\))?( unsigned)?$#', $field['Type'], $matches);

            $config = [
                'type' => $matches[1],
                'name' => $field['Field'],
                'null' => ($field['Null'] === 'YES'),
                'default' => $field['Default'],
                'unsigned' => !empty($matches[4]),
                'length' => null,
                'values' => null,
            ];

            switch ($config['type']) {
                case 'enum':
                case 'set':
                    $config['values'] = explode(',', $matches[3]);
                    break;

                default:
                    if (!isset($matches[3])) {
                        $config['length'] = null;
                    } elseif (strpos($matches[3], ',')) {
                        $config['length'] = floatval(str_replace(',', '.', $matches[3]));
                    } else {
                        $config['length'] = intval($matches[3]);
                    }
            }

            $fields[] = $config;
        }

        return $fields;
    }
}
