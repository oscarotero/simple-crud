<?php

namespace SimpleCrud\Queries\Mysql;

use SimpleCrud\SimpleCrudException;
use SimpleCrud\Queries\BaseQuery;
use SimpleCrud\Queries\ExtendedSelectionTrait;
use SimpleCrud\RowCollection;
use SimpleCrud\Entity;
use PDOStatement;
use PDO;

/**
 * Manages a database select query.
 */
class Select extends BaseQuery
{
    const MODE_ONE = 1;
    const MODE_ALL = 2;

    use ExtendedSelectionTrait;

    protected $leftJoin = [];
    protected $orderBy = [];
    protected $statement;
    protected $mode;

    /**
     * Change the mode to returns just the first row
     * 
     * @return self
     */
    public function one()
    {
        $this->mode = self::MODE_ONE;

        return $this->limit(1);
    }

    /**
     * Change the mode to returns all rows (even duplicated)
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
                return $this->entity->create($this->entity->prepareDataFromDatabase($row));
            }

            return;
        }

        $result = $this->entity->createCollection();

        if ($this->mode === self::MODE_ALL) {
            $result->idAsKey(false);
        }

        while (($row = $statement->fetch())) {
            $result[] = $this->entity->create($this->entity->prepareDataFromDatabase($row));
        }

        return $result;
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
     * @param Entity     $entity
     * @param string     $on
     * @param array|null $marks
     *
     * @return self
     */
    public function leftJoin(Entity $entity, $on = null, $marks = null)
    {
        if ($this->entity->getRelation($entity) !== Entity::RELATION_HAS_ONE) {
            throw new SimpleCrudException("The items '{$this->entity->name}' and '{$entity->name}' are no related or cannot be joined");
        }

        $this->leftJoin[] = [
            'entity' => $entity,
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
        $statement = $this->entity->getDb()->execute((string) $this, $this->marks);
        $statement->setFetchMode(PDO::FETCH_ASSOC);

        return $statement;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        $query = 'SELECT';
        $query .= ' '.static::buildFields($this->entity->name, array_keys($this->entity->fields));

        foreach ($this->leftJoin as $join) {
            $query .= ', '.static::buildFields($join['entity']->name, array_keys($join['entity']->fields), true);
        }

        $query .= $this->fieldsToString();
        $query .= ' FROM `'.$this->entity->name.'`';
        $query .= $this->fromToString();

        foreach ($this->leftJoin as $join) {
            $query .= ' LEFT JOIN `'.$join['entity']->name.'`"';

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
