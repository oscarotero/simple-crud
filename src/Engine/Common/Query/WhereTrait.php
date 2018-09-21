<?php
declare(strict_types = 1);

namespace SimpleCrud\Engine\Common\Query;

use InvalidArgumentException;
use Latitude\QueryBuilder\CriteriaInterface;
use RuntimeException;
use SimpleCrud\Fields\Field;
use SimpleCrud\Row;
use SimpleCrud\RowCollection;
use SimpleCrud\Table;
use function Latitude\QueryBuilder\criteria;
use function Latitude\QueryBuilder\field;

trait WhereTrait
{
    public function whereEquals($name, $value = null)
    {
        if (is_array($name)) {
            $this->query->whereEquals($name);
        } else {
            $this->query->whereEquals([$name => $value]);
        }

        return $this;
    }
    /**
     * @param CriteriaInterface|string $expression
     */
    public function criteria($expression): self
    {
        if (!($expression instanceof CriteriaInterface)) {
            $expression = criteria($expression);
        }

        $this->query->andWhere($expression);

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
            $this->query->andWhere($field->criteria()->isNotNull());

            return $this;
        }

        //Has many
        if ($field = $table2->getJoinField($table1)) {
            $this->query
                ->leftJoin(
                    $table2->getName(),
                    criteria('%s = %s', $field->identify(), $table1->id->identify())
                )
                ->andWhere($field->criteria()->isNotNull());

            return $this;
        }

        //Has many to many
        if ($joinTable = $table1->getJoinTable($table2)) {
            $field1 = $joinTable->getJoinField($table1);
            $field2 = $joinTable->getJoinField($table2);

            $this->query
                ->leftJoin(
                    $joinTable->getName(),
                    criteria('%s = %s', $field1->identify(), $table1->id->identify())
                )
                ->andWhere($field2->criteria()->isNotNull());

            return $this;
        }

        throw new RuntimeException(
            sprintf('The tables %s and %s are not related', $table1->getName(), $table2->getName())
        );
    }

    private function applyRowRelation($row): self
    {
        $table1 = $this->table;
        $table2 = $row->getTable();

        //Has one
        if ($field = $table1->getJoinField($table2)) {
            return $this->whereIdIs($field, $row->id);
        }

        //Has many
        if ($field = $table2->getJoinField($table1)) {
            $this->query
                ->leftJoin(
                    $table2->getName(),
                    criteria('%s = %s', $field->identify(), $table1->id->identify())
                );

            return $this->whereIdIs($table2->id, $row->id);
        }

        //Has many to many
        if ($joinTable = $table1->getJoinTable($table2)) {
            $field1 = $joinTable->getJoinField($table1);
            $field2 = $joinTable->getJoinField($table2);

            $this->query
                //->addColumns($field2->identify())
                ->leftJoin(
                    $joinTable->getName(),
                    criteria('%s = %s', $field1->identify(), $table1->id->identify())
                );

            return $this->whereIdIs($field2, $row->id);
        }

        throw new RuntimeException(
            sprintf('The tables %s and %s are not related', $table1->getName(), $table2->getName())
        );
    }

    private function whereIdIs(Field $field, $id): self
    {
        if (is_array($id)) {
            $criteria = $field->criteria()->in(...$id);
        } else {
            $criteria = $field->criteria()->eq($id);
        }

        $this->query->andWhere($criteria);

        return $this;
    }
}
