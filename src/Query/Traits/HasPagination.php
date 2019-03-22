<?php
declare(strict_types = 1);

namespace SimpleCrud\Query\Traits;

use PDO;

trait HasPagination
{
    private $page;
    private $perPage;

    public function page(int $page, int $perPage = 10): self
    {
        $this->page = $page;
        $this->perPage = $perPage;

        $this->query->page($page);
        $this->query->perPage($perPage);

        return $this;
    }

    public function getPageInfo()
    {
        $query = clone $this->query;
        $query->resetOrderBy();
        $query->resetLimit();
        $query->resetColumns();
        $query->columns('COUNT(*)');

        $statement = $query->perform();
        $statement->setFetchMode(PDO::FETCH_NUM);

        $page = $this->page;
        $count = $statement->fetch();

        return [
            'total' => $count,
            'page' => $page,
            'previous' => $page > 1 ? $page - 1 : null,
            'next' => $count > ($page * $perPage) ? $page + 1 : null,
        ];
    }
}
