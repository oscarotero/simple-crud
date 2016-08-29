<?php

namespace SimpleCrud\Queries\Mysql;

use SimpleCrud\AbstractRow;
use SimpleCrud\SimpleCrudException;
use SimpleCrud\Scheme\Scheme;

/**
 * Extended trait.
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
        $scheme = $this->table->getScheme();

        if (!isset($scheme['relations'][$table->name])) {
            throw new SimpleCrudException(sprintf('The tables %s and %s are no related', $table->name, $this->table->name));
        }

        $relation = $scheme['relations'][$table->name];

        switch ($relation[0]) {
            case Scheme::HAS_ONE:
                return $this->by($relation[1], $row->id);

            case Scheme::HAS_MANY:
                if ($table->name === $this->table->name) {
                    return $this->by($relation[1], $row->id);
                }

                return $this->byId($row->{$relation[1]});

            case Scheme::HAS_MANY_TO_MANY:
                $this->from($relation[1]);
                $this->from($table->name);

                $this->fields[] = sprintf('`%s`.`%s`', $relation[1], $relation[3]);
                $this->where(sprintf('`%s`.`%s` = `%s`.`id`', $relation[1], $relation[2], $this->table->name));
                $this->where(sprintf('`%s`.`%s` = `%s`.`id`', $relation[1], $relation[3], $table->name));
                $this->where(sprintf('`%s`.`id` IN (:%s)', $table->name, $relation[3]), [':'.$relation[3] => $row->id]);

                return $this;

            default:
                throw new SimpleCrudException(sprintf('Invalid relation type between %s and %s', $table->name, $this->table->name));
        }
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
