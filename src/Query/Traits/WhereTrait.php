<?php
declare(strict_types = 1);

namespace SimpleCrud\Query\Traits;

use InvalidArgumentException;
use RuntimeException;
use SimpleCrud\Row;
use SimpleCrud\RowCollection;
use SimpleCrud\Table;

trait WhereTrait
{
    public function whereEquals($name, $value = null)
    {
        if (is_array($name)) {
            $this->query->whereEquals($name);
        } else {
            $this->query->whereEquals([(string) $name => $value]);
        }

        return $this;
    }

    /**
     * @param Row|RowCollection|Table
     * @param mixed $related
     */
    public function relatedWith($related): self
    {
        if ($related instanceof Row || $related instanceof RowCollection) {
            return $this->applyRowRelation($related);
        }

        if ($related instanceof Table) {
            return $this->applyTableRelation($related);
        }

        throw new InvalidArgumentException(
            'Invalid argument type. Only instances of Row, Table and RowCollection are allowed'
        );
    }

    private function applyTableRelation(Table $table2): self
    {
        $table1 = $this->table;

        //Has one
        if ($field = $table1->getJoinField($table2)) {
            return $this->where("{$field} IS NOT NULL");
        }

        //Has many
        if ($field = $table2->getJoinField($table1)) {
            $this->query
                ->join(
                    'LEFT',
                    (string) $table2,
                    sprintf('%s = %s', $field, $table1->id)
                )
                ->where(sprintf('%s IS NOT NULL', $field));

            return $this;
        }

        //Has many to many
        if ($joinTable = $table1->getJoinTable($table2)) {
            $field1 = $joinTable->getJoinField($table1);
            $field2 = $joinTable->getJoinField($table2);

            $this->query
                ->join(
                    'LEFT',
                    (string) $joinTable,
                    sprintf('%s = %s', $field1, $table1->id)
                )
                ->where(sprintf('%s IS NOT NULL', $field2));

            return $this;
        }

        throw new RuntimeException(
            sprintf('The tables %s and %s are not related', $table1, $table2)
        );
    }

    private function applyRowRelation($row): self
    {
        $table1 = $this->table;
        $table2 = $row->getTable();

        //Has one
        if ($field = $table1->getJoinField($table2)) {
            return $this->whereEquals($field, $row->id);
        }

        //Has many
        if ($field = $table2->getJoinField($table1)) {
            $this->query
                ->join(
                    'LEFT',
                    (string) $table2,
                    sprintf('%s = %s', $field, $table1->id)
                );

            return $this->whereEquals($table2->id, $row->id);
        }

        //Has many to many
        if ($joinTable = $table1->getJoinTable($table2)) {
            $field1 = $joinTable->getJoinField($table1);
            $field2 = $joinTable->getJoinField($table2);

            $this->query
                ->join(
                    'LEFT',
                    (string) $joinTable,
                    sprintf('%s = %s', $field1, $table1->id)
                );

            return $this->whereEquals($field2, $row->id);
        }

        throw new RuntimeException(
            sprintf('The tables %s and %s are not related', $table1, $table2)
        );
    }
}
