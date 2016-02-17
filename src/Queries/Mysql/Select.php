<?php

namespace SimpleCrud\Queries\Mysql;

use SimpleCrud\SimpleCrudException;
use SimpleCrud\Queries\Query;
use SimpleCrud\Scheme\Scheme;
use SimpleCrud\RowCollection;
use SimpleCrud\Table;
use PDO;

/**
 * Manages a database select query.
 */
class Select extends Query
{
    const MODE_ONE = 1;
    const MODE_ALL = 2;
    const MODE_ARRAY = 3;

    use ExtendedSelectionTrait;

    protected $leftJoin = [];
    protected $orderBy = [];
    protected $statement;
    protected $mode = 2;

    /**
     * Change the mode to returns just the first row.
     * 
     * @return self
     */
    public function one()
    {
        $this->mode = self::MODE_ONE;

        return $this->limit(1);
    }

    /**
     * Change the mode to returns all rows.
     * 
     * @param bool $asArray
     * 
     * @return self
     */
    public function all($asArray = false)
    {
        $this->mode = $asArray ? self::MODE_ARRAY : self::MODE_ALL;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return Row|RowCollection|null
     */
    public function run()
    {
        $statement = $this->__invoke();

        if ($this->mode === self::MODE_ONE) {
            return ($row = $statement->fetch()) === false ? null : $this->createRow($row);
        }

        $result = $this->mode === self::MODE_ALL ? $this->table->createCollection() : [];

        while (($row = $statement->fetch())) {
            $result[] = $this->createRow($row);
        }

        return $result;
    }

    /**
     * Create a row and insert the joined rows if exist.
     *
     * @param array $data
     * 
     * @return Row
     */
    protected function createRow(array $data)
    {
        $row = $this->table->createFromDatabase($data);

        if (empty($this->leftJoin)) {
            return $row;
        }

        foreach ($this->leftJoin as $join) {
            $table = $this->table->getDatabase()->{$join['table']};
            $values = [];

            foreach (array_keys($table->fields) as $name) {
                $field = sprintf('%s.%s', $join['table'], $name);
                $values[$name] = $data[$field];
            }

            $row->{$join['table']} = empty($values['id']) ? null : $table->createFromDatabase($values);
        }

        return $row;
    }

    /**
     * Adds an ORDER BY clause.
     *
     * @param string      $orderBy
     * @param string|null $direction
     *
     * @return self
     */
    public function orderBy($orderBy, $direction = null)
    {
        if (!empty($direction)) {
            $orderBy .= ' '.$direction;
        }

        $this->orderBy[] = $orderBy;

        return $this;
    }

    /**
     * Adds a LEFT JOIN clause.
     *
     * @param string     $table
     * @param string     $on
     * @param array|null $marks
     *
     * @return self
     */
    public function leftJoin($table, $on = null, $marks = null)
    {
        $scheme = $this->table->getScheme();

        if (!isset($scheme['relations'][$table])) {
            throw new SimpleCrudException(sprintf("The tables '%s' and '%s' are not related", $this->table->name, $table));
        }

        if ($scheme['relations'][$table][0] !== Scheme::HAS_ONE) {
            throw new SimpleCrudException(sprintf("Invalid LEFT JOIN between the tables '%s' and '%s'", $this->table->name, $table));
        }

        $this->leftJoin[] = [
            'table' => $table,
            'on' => $on,
        ];

        if ($marks) {
            $this->marks += $marks;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $statement = $this->table->getDatabase()->execute((string) $this, $this->marks);
        $statement->setFetchMode(PDO::FETCH_ASSOC);

        return $statement;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        $query = 'SELECT';
        $query .= ' '.static::buildFields($this->table->name, array_keys($this->table->fields));

        foreach ($this->leftJoin as $join) {
            $query .= ', '.static::buildFields($join['table'], array_keys($this->table->getDatabase()->{$join['table']}->fields), true);
        }

        $query .= $this->fieldsToString();
        $query .= sprintf(' FROM `%s`', $this->table->name);
        $query .= $this->fromToString();

        foreach ($this->leftJoin as $join) {
            $relation = $this->table->getScheme()['relations'][$join['table']];

            $query .= sprintf(
                ' LEFT JOIN `%s` ON (`%s`.`id` = `%s`.`%s`%s)',
                $join['table'],
                $join['table'],
                $this->table->name,
                $relation[1],
                empty($join['on']) ? '' : sprintf(' AND (%s)', $join['on'])
            );
        }

        $query .= $this->whereToString();

        if (!empty($this->orderBy)) {
            $query .= ' ORDER BY '.implode(', ', $this->orderBy);
        }

        $query .= $this->limitToString();

        return $query;
    }

    /**
     * Generates the fields/tables part of a SELECT query.
     *
     * @param string $table
     * @param array  $fields
     * @param bool   $rename
     *
     * @return string
     */
    protected static function buildFields($table, array $fields, $rename = false)
    {
        $query = [];

        foreach ($fields as $field) {
            if ($rename) {
                $query[] = "`{$table}`.`{$field}` as `{$table}.{$field}`";
            } else {
                $query[] = "`{$table}`.`{$field}`";
            }
        }

        return implode(', ', $query);
    }
}
