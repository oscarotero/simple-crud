<?php
declare(strict_types = 1);

namespace SimpleCrud\Query\Traits;

use InvalidArgumentException;
use RuntimeException;
use SimpleCrud\Row;
use SimpleCrud\RowCollection;
use SimpleCrud\Table;

trait HasJoin
{
    public function leftJoin(Table $table, string $condition = null, ...$values)
    {
        return $this->join('LEFT', $table, $condition, ...$values);
    }

    public function join(string $type, Table $table2, string $condition = null, ...$values)
    {
        $table1 = $this->table;

        //Has One
        if ($field = $table1->getJoinField($table2)) {
            $this->query
                ->join(
                    $type,
                    (string) $table2,
                    sprintf('%s = %s', $field, $table2->id)
                );
                
        //Has many
        } elseif ($field = $table->getJoinField($table1)) {
            $this->query
                ->join(
                    $type,
                    (string) $table2,
                    sprintf('%s = %s', $field, $table1->id)
                );

        //Has many to many
        } elseif ($joinTable = $table1->getJoinTable($table2)) {
            $field1 = $joinTable->getJoinField($table1);
            $field2 = $joinTable->getJoinField($table2);

            $this->query
                ->join(
                    $type,
                    (string) $joinTable,
                    sprintf('%s = %s', $field1, $table1->id)
                )
                ->join(
                    $type,
                    (string) $table2,
                    sprintf('%s = %s', $field2, $table2->id)
                );

        } else {
            throw new RuntimeException(
                sprintf('The tables %s and %s are not related', $table1, $table2)
            );
        }

        if ($condition) {
            $this->query->catJoin($condition, $values);
        }

        return $this;
    }
}
