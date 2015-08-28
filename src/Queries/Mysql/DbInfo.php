<?php
namespace SimpleCrud\Queries\Mysql;

use SimpleCrud\SimpleCrud;
use PDO;

/**
 * Class to retrieve info from a mysql database
 */
class DbInfo
{
    /**
     * Build and return the query
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
     * Build and return the fields of a table
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
            preg_match('#^(\w+)#', $field['Type'], $matches);

            $fields[$field['Field']] = $matches[1];
        }

        return $fields;
    }
}
