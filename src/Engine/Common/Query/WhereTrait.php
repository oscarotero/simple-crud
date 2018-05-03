<?php
declare(strict_types = 1);

namespace SimpleCrud\Engine\Common\Query;

use InvalidArgumentException;
use Latitude\QueryBuilder\CriteriaInterface;
use RuntimeException;
use SimpleCrud\AbstractRow;
use SimpleCrud\Engine\SchemeInterface;
use SimpleCrud\Fields\Field;
use SimpleCrud\Table;
use function Latitude\QueryBuilder\criteria;
use function Latitude\QueryBuilder\field;

trait WhereTrait
{
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
     * @param AbstractRow|Table
     * @param mixed $related
     */
    public function relatedWith($related): self
    {
        if ($related instanceof AbstractRow) {
            return $this->applyRowRelation($related);
        }

        if ($related instanceof Table) {
            return $this->applyTableRelation($related);
        }

        throw new InvalidArgumentException('Invalid argument type');
    }

    private function applyTableRelation(Table $table2): self
    {
        $scheme = $this->table->getDatabase()->getScheme();
        $table1 = $this->table;

        switch ($scheme->getRelation($table1, $table2)) {
            case SchemeInterface::HAS_ONE:
                return $this->applyRelation($table1, $table2);
            case SchemeInterface::HAS_MANY:
                if ($table1 === $table2) {
                    return $this->applyRelation($table1, $table2);
                }

                return $this
                    ->applyJoinRelation($table1, $table2)
                    ->applyRelation($table1, $table2);

            case SchemeInterface::HAS_MANY_TO_MANY:
                $bridge = $scheme->getManyToManyTable($table1, $table2);

                return $this
                    ->applyJoinRelation($table1, $bridge)
                    ->applyRelation($table1, $bridge);
        }

        throw new RuntimeException(
            sprintf('The tables %s and %s are not related', $table1->getName(), $table2->getName())
        );
    }

    private function applyRowRelation(AbstractRow $row): self
    {
        $scheme = $this->table->getDatabase()->getScheme();
        $table1 = $this->table;
        $table2 = $row->getTable();

        switch ($scheme->getRelation($table1, $table2)) {
            case SchemeInterface::HAS_ONE:
                return $this->applyRelationWithId($table1, $table2, $row->id);
            case SchemeInterface::HAS_MANY:
                if ($table1 === $table2) {
                    return $this->applyRelationWithId($table1, $table2, $row->id);
                }

                return $this->whereIdIs($table1->id, $row->id);
            case SchemeInterface::HAS_MANY_TO_MANY:
                $bridge = $scheme->getManyToManyTable($table1, $table2);

                $this->query->addColumns($bridge->getJoinField($table2)->identify());

                return $this
                    ->applyJoinRelation($table1, $bridge)
                    ->applyRelationWithId($bridge, $table2, $row->id);
        }

        throw new RuntimeException(
            sprintf('The tables %s and %s are not related', $table1->getName(), $table2->getName())
        );
    }

    private function applyJoinRelation(Table $table1, Table $table2): self
    {
        $this->query->leftJoin(
            $table2->getName(),
            criteria(
                '%s = %s',
                $table2->getJoinField($table1)->identify(),
                $table1->id->identify()
            )
        );

        return $this;
    }

    private function applyRelationWithId(Table $table1, Table $table2, $id): self
    {
        return $this->whereIdIs($table1->getJoinField($table2), $id);
    }

    private function applyRelation(Table $table1, Table $table2): self
    {
        $this->query->andWhere($table1->getJoinField($table2)->criteria()->isNotNull());

        return $this;
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
