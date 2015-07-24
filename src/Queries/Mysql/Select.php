<?php
namespace SimpleCrud\Queries\Mysql;

use SimpleCrud\Queries\QueryInterface;
use SimpleCrud\RowCollection;
use SimpleCrud\Row;
use SimpleCrud\RowInterface;
use SimpleCrud\Entity;
use SimpleCrud\SimpleCrudException;
use PDOStatement;
use PDO;

/**
 * Manages a database select query in Mysql databases
 */
class Select implements QueryInterface
{
    protected $entity;

    protected $fields = [];
    protected $from = [];
    protected $where = [];
    protected $marks = [];
    protected $leftJoin = [];
    protected $orderBy = [];
    protected $limit;
    protected $offset;

    /**
     * @see QueryInterface
     * 
     * {@inheritdoc}
     */
    public static function getInstance(Entity $entity)
    {
        return new static($entity);
    }

    /**
     * @see QueryInterface
     * 
     * $entity->select($where, $marks, $orderBy, $limit)
     * 
     * {@inheritdoc}
     */
    public static function execute(Entity $entity, array $args)
    {
        $select = self::getInstance($entity);

        if (isset($args[0])) {
            $select->where($args[0], isset($args[1]) ? $args[1] : null);
        }

        if (isset($args[2])) {
            $select->orderBy($args[2]);
        }

        if (isset($args[3])) {
            if ($args[3] === true) {
                return $select->one();
            }

            $select->limit($args[3]);
        }

        return $select->all();
    }

    /**
     * Constructor
     * 
     * @param Entity $entity
     */
    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
    }

    /**
     * Adds new extra table to the query
     * 
     * @param string     $table
     * 
     * @return self
     */
    public function from($table)
    {
        $this->from[] = $table;

        return $this;
    }

    /**
     * Adds a WHERE clause
     * 
     * @param string     $where
     * @param null|array $marks
     * 
     * @return self
     */
    public function where($where, $marks = null)
    {
        $this->where[] = $where;

        if ($marks) {
            $this->marks += $marks;
        }

        return $this;
    }

    /**
     * Adds a WHERE according with the relation of other entity
     * 
     * @param RowInterface $id
     * 
     * @return self
     */
    public function relatedWith(RowInterface $row)
    {
        if ($this->entity->hasOne($row)) {
            return $this->by($row->getEntity()->foreignKey, $id->get('id'));
        }

        if ($this->entity->hasMany($row)) {
            return $this->byId($row->get($this->entity->foreignKey));
        }

        throw new SimpleCrudException("The tables {$this->entity->table} and {$id->getEntity()->table} are no related");
    }

    /**
     * Adds a WHERE field = :value clause
     * 
     * @param string $field
     * @param int|array $value
     * 
     * @return self
     */
    public function by($field, $value)
    {
        if (is_array($value)) {
            return $this->where("`{$this->entity->table}`.`{$field}` IN (:{$field})", [":{$field}" => $value]);
        }
        
        return $this->where("`{$this->entity->table}`.`{$field}` = :{$field}", [":{$field}" => $value]);
    }

    /**
     * Adds a WHERE id = :id clause
     * 
     * @param int|array $id
     * 
     * @return self
     */
    public function byId($id)
    {
        $limit = is_array($id) ? count($id) : 1;

        return $this->limit($limit)->by('id', $id);
    }

    /**
     * Adds an ORDER BY clause
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
     * Adds a LIMIT clause
     * 
     * @param integer $limit
     * 
     * @return self
     */
    public function limit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Adds an offset to the LIMIT clause
     * 
     * @param integer $offset
     * 
     * @return self
     */
    public function offset($offset)
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Adds a LEFT JOIN clause
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
            throw new SimpleCrudException("The items '{$this->entity->table}' and '{$entity->table}' are no related or cannot be joined");
        }

        $this->leftJoin[] = [
            'entity' => $entity,
            'on' => $on
        ];

        if ($marks) {
            $this->marks += $marks;
        }

        return $this;
    }

    /**
     * Adds new marks to the query
     * 
     * @param array $marks
     * 
     * @return self
     */
    public function marks(array $marks)
    {
        $this->marks += $marks;

        return $this;
    }

    /**
     * Run the query and return a statement with the result
     * 
     * @return PDOStatement
     */
    public function run()
    {
        $statement = $this->entity->getDb()->execute((string) $this, $this->marks);
        $statement->setFetchMode(PDO::FETCH_ASSOC);

        return $statement;
    }

    /**
     * Run the query and return all values
     * 
     * @return RowCollection
     */
    public function all()
    {
        $statement= $this->run();
        $result = $this->entity->createCollection();

        while (($row = $statement->fetch())) {
            $result[] = $this->entity->create($this->entity->dataFromDatabase($row));
        }

        return $result;
    }

    /**
     * Run the query and return the first value
     * 
     * @return RowCollection
     */
    public function one()
    {
        $row = $this->run()->fetch();

        if ($row !== false) {
            return $this->entity->create($this->entity->dataFromDatabase($row));
        }
    }

    /**
     * Build and return the query
     * 
     * @return string
     */
    public function __toString()
    {
        $query = 'SELECT';
        $query .= ' '.static::buildFields($this->entity->table, array_keys($this->entity->fields));

        foreach ($this->leftJoin as $join) {
            $query .= ', '.static::buildFields($join['entity']->table, array_keys($join['entity']->fields), $join['entity']->name);
        }

        $query .= ' FROM `'.$this->entity->table.'`';

        if (!empty($this->from)) {
            $query .= ', `'.implode('`, `', $this->from).'`';
        }

        foreach ($this->leftJoin as $join) {
            $query .= ' LEFT JOIN `'.$join['entity']->table.'`"';

            if (!empty($join['on'])) {
                $query .= ' ON ('.$join['on'].')';
            }
        }

        if (!empty($this->where)) {
            $query .= ' WHERE ('.implode(') AND (', $this->where).')';
        }

        if (!empty($this->orderBy)) {
            $query .= ' ORDER BY '.implode(', ', $this->orderBy);
        }

        if (!empty($this->limit)) {
            $query .= ' LIMIT';

            if (!empty($this->offset)) {
                $query .= ' '.$this->offset.',';
            }

            $query .= ' '.$this->limit;
        }

        return $query;
    }

    /**
     * Generates the fields/tables part of a SELECT query
     *
     * @param string      $table
     * @param array       $fields
     * @param string|null $rename
     *
     * @return string
     */
    protected static function buildFields($table, array $fields, $rename = null)
    {
        $query = [];

        foreach ($fields as $field) {
            if ($rename) {
                $query[] = "`{$table}`.`{$field}` as `{$rename}.{$field}`";
            } else {
                $query[] = "`{$table}`.`{$field}`";
            }
        }

        return implode(', ', $query);
    }
}
