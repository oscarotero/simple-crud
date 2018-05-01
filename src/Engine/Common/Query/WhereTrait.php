<?php
declare(strict_types = 1);

namespace SimpleCrud\Engine\Common\Query;

use Latitude\QueryBuilder\CriteriaInterface;
use function Latitude\QueryBuilder\field;
use function Latitude\QueryBuilder\identify;
use function Latitude\QueryBuilder\criteria;
use SimpleCrud\AbstractRow;
use SimpleCrud\Fields\Field;
use SimpleCrud\Engine\SchemeInterface;

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

    public function relatedWith(AbstractRow $row): self
    {
        $scheme = $this->table->getDatabase()->getScheme();
        $scheme->applyRelationCriteria($this->query, $this->table, $row->getTable(), $row->id);

        return $this;
    }
}
