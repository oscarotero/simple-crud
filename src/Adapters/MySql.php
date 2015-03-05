<?php
namespace SimpleCrud\Adapters;

use PDOStatement;

/**
 * Adapter class for MySql databases
 */
class Mysql extends Adapter implements AdapterInterface {

    /**
     * {@inheritdoc}
     */
    public function executeSelect(array $fields, array $joins = null, $where = null, array $marks = null, $orderBy = null, $limit = null)
    {
        $query = ['SELECT '.static::generateSelect($fields, $joins)];

        if (($joins = static::generateJoins($joins)) !== null) {
            $query[] = $joins;
        }

        if (($where = static::generateWhere($where)) !== null) {
            $query[] = $where;
        }

        if (($orderBy = static::generateOrderBy($orderBy)) !== null) {
            $query[] = $orderBy;
        }

        if (($limit = static::generateLimit($limit)) !== null) {
            $query[] = $limit;
        }

        return $this->execute(implode(' ', $query), $marks);
    }

    /**
     * {@inheritdoc}
     */
    public function count($table, $where = null, array $marks = null, $limit = null)
    {
        $query = ['SELECT COUNT(*)'];
        $query[] = "FROM `{$table}`";

        if (($where = static::generateWhere($where)) !== null) {
            $query[] = $where;
        }

        if (($limit = static::generateLimit($limit)) !== null) {
            $query[] = $limit;
        }

        $statement = $this->execute(implode(' ', $query), $marks);
        $result = $statement->fetch(\PDO::FETCH_NUM);

        return (int)$result[0];
    }

    /**
     * {@inheritdoc}
     */
    public function insert($table, array $data = null, $duplicateKeyErrors = false)
    {
        if (empty($data)) {
            return "INSERT INTO `{$table}` (`id`) VALUES (NULL)";
        }

        $fields = array_keys($data);

        $query = ["INSERT INTO `{$table}`"];
        $query[] = '(`'.implode('`, `', $fields).'`)';
        $query[] = 'VALUES';
        $query[] = '(:'.implode(', :', $fields).')';

        if (!$duplicateKeyErrors) {
            $query[] = 'ON DUPLICATE KEY UPDATE';
            $query[] = 'id = LAST_INSERT_ID(id), '.static::generateUpdateFields($fields);
        }

        $marks = [];

        foreach ($data as $field => $value) {
            $marks[":{$field}"] = $value;
        }

        return $this->execute(implode(' ', $query), $marks);
    }

    /**
     * {@inheritdoc}
     */
    public function update($table, array $data, $where = null, array $marks = null, $limit = null)
    {
        $query = ["UPDATE `{$table}`"];
        $query[] = 'SET '.static::generateUpdateFields(array_keys($data), '__');

        if (($where = static::generateWhere($where)) !== null) {
            $query[] = $where;
        }

        if (($limit = static::generateLimit($limit)) !== null) {
            $query[] = $limit;
        }

        $marks = $marks ?: [];

        foreach ($data as $field => $value) {
            $marks[":__{$field}"] = $value;
        }

        return $this->execute(implode(' ', $query), $marks);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($table, $where = null, array $marks = null, $limit = null)
    {
        $query = ["DELETE FROM `{$table}`"];

        if (($where = static::generateWhere($where)) !== null) {
            $query[] = $where;
        }

        if (($limit = static::generateLimit($limit)) !== null) {
            $query[] = $limit;
        }

        $this->execute(implode(' ', $query), $marks);
    }

    /**
     * {@inheritdoc}
     */
    public function getFields ($table) {
        $fields = [];

        foreach ($this->execute("DESCRIBE `{$table}`")->fetchAll() as $field) {
            preg_match('#^(\w+)#', $field['Type'], $matches);

            $fields[$field['Field']] = $matches[1];
        }

        return $fields;
    }

    /**
     * {@inheritdoc}
     */
    public function getTables () {
        return $this->execute('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN, 0);
    }

    /**
     * Generates the code for the fields in an update/insert query
     * 
     * @param array  $fields
     * @param string $markPrefix
     * 
     * @return string
     */
    protected static function generateUpdateFields(array $fields, $markPrefix = '')
    {
        $update = [];

        foreach ($fields as $name) {
            $update[] = "`{$name}` = :{$markPrefix}{$name}";
        }

        return implode(', ', $update);
    }

    /**
     * Generates the fields/tables part of a SELECT query
     * 
     * @param array      $selectFields
     * @param array|null $joins
     * 
     * @return string
     */
    protected static function generateSelect(array $selectFields, array $joins = null)
    {
        $escapedFields = [];
        $escapedTables = [];

        foreach ($selectFields as $table => $fields) {
            $escapedTables[] = "`{$table}`";

            foreach ($fields as $field) {
                $escapedFields[] = "`{$table}`.`{$field}`";
            }
        }

        if (!empty($joins)) {
            foreach ($joins as $join) {
                if (!isset($join['fields'])) {
                    continue;
                }

                foreach ($join['fields'] as $field) {
                    $escapedFields[] = "`{$join['table']}`.`{$field}` as `{$join['name']}.{$field}`";
                }
            }
        }

        return implode(',', $escapedFields).' FROM '.implode(',', $escapedTables);
    }

    /**
     * Generate a LEFT JOIN clause
     * 
     * @param mixed $joins
     * 
     * @return string|null
     */
    protected static function generateJoins($joins)
    {
        if (empty($joins)) {
            return;
        }

        $escapedJoins = [];

        foreach ($joins as $join) {
            $currentJoin = ['LEFT JOIN'];
            $currentJoin[] = "`{$join['table']}`";

            if (!empty($join['on'])) {
                $currentJoin[] = static::generateWhere($join['on'], 'ON');
            }

            $escapedJoins[] = $currentJoin;
        }

        return implode(' ', $escapedJoins);
    }

    /**
     * Generate a WHERE clause
     * 
     * @param mixed $where
     * 
     * @return string|null
     */
    protected static function generateWhere($where, $clause = 'WHERE')
    {
        if (empty($where)) {
            return;
        }

        if (is_array($where)) {
            $where = implode(') AND (', $where);
        }

        return "{$clause} ($where)";
    }

    /**
     * Generate an ORDER BY clause
     * 
     * @param mixed $orderBy
     * 
     * @return string|null
     */
    protected static function generateOrderBy($orderBy)
    {
        if (empty($orderBy)) {
            return;
        }

        return 'ORDER BY '.(is_array($orderBy) ? implode(', ', $orderBy) : $orderBy);
    }

    /**
     * Generate a LIMIT clause
     * 
     * @param mixed $limit
     * 
     * @return string|null
     */
    protected static function generateLimit($limit)
    {
        if (empty($limit)) {
            return;
        }

        if ($limit === true) {
            return 'LIMIT 1';
        }

        return 'LIMIT '.(is_array($limit) ? implode(', ', $limit) : $limit);
    }
}
