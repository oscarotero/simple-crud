<?php

namespace SimpleCrud\Scheme;

use PDO;

/**
 * Class to retrieve info from a sqlite database.
 */
class Sqlite extends Scheme
{
    /**
     * {@inheritdoc}
     */
    protected function getTables()
    {
        return $this->db->execute('SELECT name FROM sqlite_master WHERE (type="table" OR type="view") AND name != "sqlite_sequence"')->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTableFields($table)
    {
        $result = $this->db->execute("pragma table_info(`{$table}`)")->fetchAll(PDO::FETCH_ASSOC);
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
