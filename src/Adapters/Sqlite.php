<?php
namespace SimpleCrud\Adapters;

use PDO;

/**
 * Adapter class for Sqlite databases
 */
class Sqlite extends MySql implements AdapterInterface
{
    protected $updateDeleteLimit;

    /**
     * {@inheritdoc}
     */
    public function getFields ($table) {
        $result = $this->execute("pragma table_info({$table})")->fetchAll(PDO::FETCH_ASSOC);
        $fields = [];

        foreach ($result as $field) {
            $fields[$field['name']] = strtolower($field['type']);
        }

        return $fields;
    }

    /**
     * {@inheritdoc}
     */
    public function getTables () {
        return $this->execute("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    /**
     * {@inheritdoc}
     */
    public function update($table, array $data, $where = null, array $marks = null, $limit = null)
    {
        if ($this->hasUpdateDeleteLimit()) {
            return parent::update($table, $data, $where, $marks, $limit);
        }

        return parent::update($table, $data, $where, $marks);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($table, $where = null, array $marks = null, $limit = null)
    {
        if ($this->hasUpdateDeleteLimit()) {
            return parent::delete($table, $where, $marks, $limit);
        }

        return parent::delete($table, $where, $marks);
    }

    /**
     * Check whether or not the sqlite database allows LIMIT on UPDATE and DELETE queries
     * 
     * @return boolean
     */
    public function hasUpdateDeleteLimit()
    {
        if ($this->updateDeleteLimit !== null) {
            return $this->updateDeleteLimit;
        }

        $options = $this->execute("pragma compile_options")->fetchAll(PDO::FETCH_COLUMN);

        return $this->updateDeleteLimit = in_array('ENABLE_UPDATE_DELETE_LIMIT', $options);
    }
}
