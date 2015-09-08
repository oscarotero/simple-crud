<?php
namespace SimpleCrud\Queries;

use SimpleCrud\RowInterface;

/**
 * Common function to manage WHERE clause
 * 
 * @property \SimpleCrud\Entity $entity
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
     *
     * @return self
     */
    public function relatedWith(RowInterface $row)
    {
        $entity = $row->getEntity();

        if ($this->entity->hasOne($entity)) {
            return $this->by($entity->foreignKey, $row->get('id'));
        }

        if ($this->entity->hasMany($entity)) {
            return $this->byId($row->get($this->entity->foreignKey));
        }

        $bridge = $this->entity->getBridge($entity);

        if ($bridge) {
            $this->from($bridge->name);
            $this->from($entity->name);

            $this->fields[] = "`{$bridge->name}`.`{$entity->foreignKey}`";

            $this->where("`{$bridge->name}`.`{$this->entity->foreignKey}` = `{$this->entity->name}`.`id`");
            $this->where("`{$bridge->name}`.`{$entity->foreignKey}` = `{$entity->name}`.`id`");
            $this->where("`{$entity->name}`.`id` IN (:{$bridge->name})", [":{$bridge->name}" => $row->get('id')]);

            return $this;
        }

        throw new SimpleCrudException("The tables {$this->entity->name} and {$entity->name} are no related");
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
