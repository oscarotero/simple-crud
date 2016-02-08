<?php

namespace SimpleCrud\Queries;

use SimpleCrud\AbstractRow;

/**
 * Extended trait
 *
 * @property \SimpleCrud\Table $table
 */
trait ExtendedSelectionTrait
{
    use SelectionTrait;

    protected $from = [];
    protected $fields = [];

    /**
     * Adds new extra table to the query.
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
     * Adds a new extra field to the query.
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
     * Adds a WHERE according with the relation of other table.
     *
     * @param AbstractRow $row
     *
     * @return self
     */
    public function relatedWith(AbstractRow $row)
    {
        $table = $row->getTable();

        if ($this->table->hasOne($table)) {
            return $this->by($table->foreignKey, $row->get('id'));
        }

        if ($this->table->hasMany($table)) {
            return $this->byId($row->get($this->table->foreignKey));
        }

        $bridge = $this->table->getBridge($table);

        if ($bridge) {
            $this->from($bridge->name);
            $this->from($table->name);

            $this->fields[] = "`{$bridge->name}`.`{$table->foreignKey}`";

            $this->where("`{$bridge->name}`.`{$this->table->foreignKey}` = `{$this->table->name}`.`id`");
            $this->where("`{$bridge->name}`.`{$table->foreignKey}` = `{$table->name}`.`id`");
            $this->where("`{$table->name}`.`id` IN (:{$bridge->name})", [":{$bridge->name}" => $row->get('id')]);

            return $this;
        }

        throw new SimpleCrudException("The tables {$this->table->name} and {$table->name} are no related");
    }

    /**
     * add extra fields to the code.
     *
     * @return string
     */
    protected function fieldsToString($prepend = ', ')
    {
        return $this->fields ? $prepend.implode(', ', $this->fields) : '';
    }

    /**
     * add extra fields to the code.
     *
     * @return string
     */
    protected function fromToString($prepend = ', ')
    {
        return $this->from ? $prepend.'`'.implode('`, `', $this->from).'`' : '';
    }
}
