<?php
namespace SimpleCrud\Queries\Mysql;

use SimpleCrud\RowInterface;

/**
 * Common function to manage WHERE clause
 */
trait WhereExtendedTrait
{
    use WhereTrait;

    protected $from = [];
    protected $fields = [];

    /**
     * Adds new extra table to the query
     *
     * @param string $table
     *
     * @return self
     */
    public function from($table)
    {
        $this->from[] = $table;

        return $this;
    }

    /**
     * Adds a new extra field to the query
     *
     * @param string $field
     *
     * @return self
     */
    public function field($field)
    {
        $this->fields[] = $field;

        return $this;
    }

    /**
     * Adds a WHERE according with the relation of other entity
     *
     * @param RowInterface $row
     * @param string       $through
     *
     * @return self
     */
    public function relatedWith(RowInterface $row, $through = null)
    {
        $entity = $row->getEntity();

        if ($through !== null) {
            $through = $this->entity->getDb()->$through;

            if (!$through->hasOne($entity)) {
                throw new SimpleCrudException("The relationship between '{$through->table}' and '{$entity->table}' must be RELATION_HAS_ONE");
            }
            if (!$through->hasOne($this->entity)) {
                throw new SimpleCrudException("The relationship between '{$through->table}' and '{$this->entity->table}' must be RELATION_HAS_ONE");
            }

            $this->from($through->table);
            $this->from($entity->table);

            $this->fields[] = "`{$through->table}`.`{$entity->foreignKey}`";

            $this->where("`{$through->table}`.`{$this->entity->foreignKey}` = `{$this->entity->table}`.`id`");
            $this->where("`{$through->table}`.`{$entity->foreignKey}` = `{$entity->table}`.`id`");
            $this->where("`{$entity->table}`.`id` IN (:{$through->name})", [":{$through->name}" => $row->get('id')]);

            return $this;
        }

        if ($this->entity->hasOne($entity)) {
            return $this->by($entity->foreignKey, $row->get('id'));
        }

        if ($this->entity->hasMany($entity)) {
            return $this->byId($row->get($this->entity->foreignKey));
        }

        throw new SimpleCrudException("The tables {$this->entity->table} and {$entity->table} are no related");
    }

    /**
     * add extra fields to the code
     *
     * @return string
     */
    protected function fieldsToString($prepend = ', ')
    {
        return $this->fields ? $prepend.implode(', ', $this->fields) : '';
    }

    /**
     * add extra fields to the code
     *
     * @return string
     */
    protected function fromToString($prepend = ', ')
    {
        return $this->from ? $prepend.'`'.implode('`, `', $this->from).'`' : '';
    }
}
