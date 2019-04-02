<?php
declare(strict_types = 1);

namespace SimpleCrud\Queries\Traits;

use RuntimeException;
use SimpleCrud\Table;

trait HasJoinRelation
{
    public function joinRelation(Table $table2): self
    {
        $table1 = $this->table;

        //Has One
        if ($field = $table1->getJoinField($table2)) {
            $this->query
                ->join(
                    'LEFT',
                    (string) $table2,
                    sprintf('%s = %s', $field, $table2->id)
                );

        //Has many
        } elseif ($field = $table2->getJoinField($table1)) {
            $this->query
                ->join(
                    'LEFT',
                    (string) $table2,
                    sprintf('%s = %s', $field, $table1->id)
                );

        //Has many to many
        } elseif ($joinTable = $table1->getJoinTable($table2)) {
            $field1 = $joinTable->getJoinField($table1);
            $field2 = $joinTable->getJoinField($table2);

            $this->query
                ->join(
                    'LEFT',
                    (string) $joinTable,
                    sprintf('%s = %s', $field1, $table1->id)
                )
                ->join(
                    'LEFT',
                    (string) $table2,
                    sprintf('%s = %s', $field2, $table2->id)
                );
        } else {
            throw new RuntimeException(
                sprintf('The tables %s and %s are not related', $table1, $table2)
            );
        }

        return $this;
    }
}
