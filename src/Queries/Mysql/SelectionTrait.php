<?php

namespace SimpleCrud\Queries\Mysql;

/**
 * Trait with common functions used in queries.
 *
 * @property \SimpleCrud\Table $table
 */
trait SelectionTrait
{
    protected $where = [];
    protected $marks = [];
    protected $limit;
    protected $offset;

    /**
     * Adds new marks to the query.
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
     * Adds a WHERE clause.
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
     * Adds a OR WHERE clause.
     *
     * @param string     $where
     * @param null|array $marks
     *
     * @return self
     */
    public function orWhere($where, $marks = null)
    {
        if (!isset($this->where['or'])) {
            $this->where['or'] = [$where];
        } else {
            $this->where['or'][] = $where;
        }

        if ($marks) {
            $this->marks += $marks;
        }

        return $this;
    }

    /**
     * Adds a WHERE field = :value clause.
     *
     * @param string         $field
     * @param null|int|array $value
     *
     * @return self
     */
    public function by($field, $value)
    {
        if (is_array($value)) {
            if (empty($value)) {
                return $this->where("`{$this->table->getName()}`.`{$field}` IS NULL");
            }

            return $this->where("`{$this->table->getName()}`.`{$field}` IN (:{$field})", [":{$field}" => $value]);
        }

        if ($value === null) {
            return $this->where("`{$this->table->getName()}`.`{$field}` IS NULL");
        }

        return $this->where("`{$this->table->getName()}`.`{$field}` = :{$field}", [":{$field}" => $value]);
    }

    /**
     * Adds a WHERE id = :id clause.
     *
     * @param null|int|array $id
     *
     * @return self
     */
    public function byId($id)
    {
        if ($this->limit === null) {
            $this->limit(is_array($id) ? count($id) : 1);
        }

        return $this->by('id', $id);
    }

    /**
     * Adds a LIMIT clause.
     *
     * @param int $limit
     *
     * @return self
     */
    public function limit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Adds an offset to the LIMIT clause.
     *
     * @param int $offset
     *
     * @return self
     */
    public function offset($offset)
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Generate WHERE clause.
     *
     * @return string
     */
    protected function whereToString()
    {
        if (!empty($this->where)) {
            if (isset($this->where['or'])) {
                $this->where['or'] = '('.implode(') OR (', $this->where['or']).')';
            }

            return ' WHERE ('.implode(') AND (', $this->where).')';
        }

        return '';
    }

    /**
     * Generate LIMIT clause.
     *
     * @return string
     */
    protected function limitToString()
    {
        if (!empty($this->limit)) {
            $query = ' LIMIT';

            if (!empty($this->offset)) {
                $query .= ' '.$this->offset.',';
            }

            return $query.' '.$this->limit;
        }

        return '';
    }
}
