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

    use ExtendedSelectionTrait;

    protected $join = [];
    protected $orderBy = [];
    protected $statement;
    protected $mode = 2;
    protected $page;

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
     * @return self
     */
    public function all()
    {
        $this->mode = self::MODE_ALL;

        return $this;
    }

    /**
     * Paginate the results
     *
     * @param int|string $page
     * @param int|null   $limit
     *
     * @return self
     */
    public function page($page, $limit = null) {
        $this->page = (int) $page;

        if ($this->page < 1) {
            $this->page = 1;
        }

        if ($limit !== null) {
            $this->limit($limit);
        }

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

        $result = $this->table->createCollection();

        while (($row = $statement->fetch())) {
            $result[] = $this->createRow($row);
        }

        if ($this->page !== null) {
            $current = $this->page;
            $next = $result->count() < $this->limit ? null : $current + 1;
            $prev = $current > 1 ? $current - 1 : null;

            $result->setMethod('getPage', function () use ($current) {
                return $current;
            });

            $result->setMethod('getNextPage', function () use ($next) {
                return $next;
            });

            $result->setMethod('getPrevPage', function () use ($prev) {
                return $prev;
            });
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
    public function createRow(array $data)
    {
        $row = $this->table->createFromDatabase($data);

        if (empty($this->join)) {
            return $row;
        }

        foreach ($this->join as $joins) {
            foreach ($joins as $join) {
                $table = $this->table->getDatabase()->{$join['table']};
                $values = [];

                foreach (array_keys($table->getScheme()['fields']) as $name) {
                    $field = sprintf('%s.%s', $join['table'], $name);
                    $values[$name] = $data[$field];
                }

                $row->{$join['table']} = empty($values['id']) ? null : $table->createFromDatabase($values);
            }
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
        return $this->join('LEFT', $table, $on, $marks);
    }

    /**
     * Adds a JOIN clause.
     *
     * @param string     $join
     * @param string     $table
     * @param string     $on
     * @param array|null $marks
     *
     * @return self
     */
    public function join($join, $table, $on = null, $marks = null)
    {
        $join = strtoupper($join);
        $scheme = $this->table->getScheme();

        if (!isset($scheme['relations'][$table])) {
            throw new SimpleCrudException(sprintf("The tables '%s' and '%s' are not related", $this->table->getName(), $table));
        }

        if ($scheme['relations'][$table][0] !== Scheme::HAS_ONE) {
            throw new SimpleCrudException(sprintf("Invalid %s JOIN between the tables '%s' and '%s'", $join, $this->table->getName(), $table));
        }

        if (!isset($this->join[$join])) {
            $this->join[$join] = [];
        }

        $this->join[$join][] = [
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
        if ($this->page !== null) {
            $this->offset = ($this->page * $this->limit) - $this->limit;
        }

        $query = 'SELECT';
        $query .= ' '.static::buildFields($this->table);

        foreach ($this->join as $joins) {
            foreach ($joins as $join) {
                $query .= ', '.static::buildFields($this->table->getDatabase()->{$join['table']}, true);
            }
        }

        $query .= $this->fieldsToString();
        $query .= sprintf(' FROM `%s`', $this->table->getName());
        $query .= $this->fromToString();

        foreach ($this->join as $type => $joins) {
            foreach ($joins as $join) {
                $relation = $this->table->getScheme()['relations'][$join['table']];

                $query .= sprintf(
                    ' %s JOIN `%s` ON (`%s`.`id` = `%s`.`%s`%s)',
                    $type,
                    $join['table'],
                    $join['table'],
                    $this->table->getName(),
                    $relation[1],
                    empty($join['on']) ? '' : sprintf(' AND (%s)', $join['on'])
                );
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
     * @param Table $table
     * @param bool  $rename
     *
     * @return string
     */
    protected static function buildFields(Table $table, $rename = false)
    {
        $tableName = $table->getName();
        $query = [];

        foreach ($table->getFields() as $fieldName => $field) {
            $query[] = $field->getSelectExpression($rename ? "{$tableName}.{$fieldName}" : null);
        }

        return implode(', ', $query);
    }
}
