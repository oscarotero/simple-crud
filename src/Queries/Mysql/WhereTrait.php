<?php
namespace SimpleCrud\Queries\Mysql;

use SimpleCrud\Queries\Mysql;
use PDO;

/**
 * Common function to manage WHERE clause
 */
trait WhereTrait
{
    protected $where = [];
    protected $marks = [];

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
}
