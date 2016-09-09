<?php

namespace SimpleCrud\Scheme;

use PDO;

/**
 * Class to retrieve info from a mysql database.
 */
class Mysql extends Scheme
{
    /**
     * {@inheritdoc}
     */
    protected function getTables()
    {
        return $this->db->execute('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTableFields($table)
    {
        $result = $this->db->execute("DESCRIBE `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
        $fields = [];

        foreach ($result as $field) {
            $name = $field['Field'];

            preg_match('#^(\w+)(\((.+)\))?( unsigned)?#', $field['Type'], $matches);

            $config = [
                'type' => $matches[1],
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

            $fields[$name] = $config;
        }

        return $fields;
    }
}
