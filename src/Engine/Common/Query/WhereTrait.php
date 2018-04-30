<?php
declare(strict_types = 1);

namespace SimpleCrud\Engine\Common\Query;

use Latitude\QueryBuilder\CriteriaInterface;

trait WhereTrait
{
    /**
     * @param CriteriaInterface|string $expression
     */
    public function criteria($expression): self
    {
        if (!($expression instanceof CriteriaInterface)) {
            $expression = $this->builder->criteria($expression);
        }

        $this->query->andWhere($expression);

        return $this;
    }
}
