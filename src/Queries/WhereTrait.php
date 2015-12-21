<?php

namespace SimpleCrud\Queries;

/**
 * Common function to manage WHERE clause.
 *
 * @property \SimpleCrud\Entity $entity
 */
trait WhereTrait
{
    protected $where = [];
    protected $marks = [];

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
                return $this->where("`{$this->entity->name}`.`{$field}` IS NULL");
            }

            return $this->where("`{$this->entity->name}`.`{$field}` IN (:{$field})", [":{$field}" => $value]);
        }

        if ($value === null) {
            return $this->where("`{$this->entity->name}`.`{$field}` IS NULL");
        }

        return $this->where("`{$this->entity->name}`.`{$field}` = :{$field}", [":{$field}" => $value]);
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
        $limit = is_array($id) ? count($id) : 1;

        return $this->limit($limit)->by('id', $id);
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
}
