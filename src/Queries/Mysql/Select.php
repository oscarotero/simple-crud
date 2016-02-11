<?php

namespace SimpleCrud\Queries\Mysql;

use SimpleCrud\SimpleCrudException;
use SimpleCrud\Queries\Query;
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

    use ExtendedSelectionTrait;

    protected $leftJoin = [];
    protected $orderBy = [];
    protected $statement;
    protected $mode;

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
     * Change the mode to returns all rows (even duplicated).
     * 
     * @return self
     */
    public function all()
    {
        $this->mode = self::MODE_ALL;

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

        //Returns one
        if ($this->mode === self::MODE_ONE) {
            $row = $statement->fetch();

            if ($row !== false) {
                return $this->table->createFromDatabase($row);
            }

            return;
        }

        $result = $this->table->createCollection();

        if ($this->mode === self::MODE_ALL) {
            $result->idAsKey(false);
        }

        while (($row = $statement->fetch())) {
            $result[] = $this->table->createFromDatabase($row);
        }

        return $result;

/* left-join:
                $joins = [];

        foreach ($data as $key => &$value) {
            if (isset($this->fields[$key])) {
                $value = $this->fields[$key]->dataFromDatabase($value);
                continue;
            }

            if (strpos($key, '.') !== false) {
                list($name, $field) = explode('.', $key, 2);

                if (!isset($joins[$name])) {
                    $joins[$name] = [];
                }

                $joins[$name][$field] = $value;

                unset($data[$key]);
            }
        }

        if (!is_array($data = $this->dataFromDatabase($data))) {
            throw new SimpleCrudException('Data not valid');
        }

        //handle left-joins
        foreach ($joins as $key => $values) {
            $table = $this->getDatabase()->$key;

            $data[$key] = $table->create($table->prepareDataFromDatabase($values));
        }

        return $data;
        */
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
     * @param Table      $table
     * @param string     $on
     * @param array|null $marks
     *
     * @return self
     */
    public function leftJoin(Table $table, $on = null, $marks = null)
    {
        if (!$this->table->hasOne($table)) {
            throw new SimpleCrudException("Invalid LEFT JOIN between the tables '{$this->table->name}' and '{$table->name}'");
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
            $query .= ', '.static::buildFields($join['table']->name, array_keys($join['table']->fields), true);
        }

        $query .= $this->fieldsToString();
        $query .= ' FROM `'.$this->table->name.'`';
        $query .= $this->fromToString();

        foreach ($this->leftJoin as $join) {
            $query .= ' LEFT JOIN `'.$join['table']->name.'`"';

            if (!empty($join['on'])) {
                $query .= ' ON ('.$join['on'].')';
            }
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
