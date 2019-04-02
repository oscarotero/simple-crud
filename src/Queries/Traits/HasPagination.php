<?php
declare(strict_types = 1);

namespace SimpleCrud\Queries\Traits;

use PDO;

trait HasPagination
{
    private $page;
    private $perPage = 10;

    public function page(int $page): self
    {
        $this->page = $page;
        $this->query->page($page);

        return $this;
    }

    public function perPage(int $perPage = 10): self
    {
        $this->perPage = $perPage;
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
        $count = intval($count[0]);

        return [
            'totalRows' => $count,
            'totalPages' => (int) ceil($count / $this->perPage),
            'currentPage' => $count ? $page : null,
            'previousPage' => $page > 1 ? $page - 1 : null,
            'nextPage' => $count > ($page * $this->perPage) ? $page + 1 : null,
        ];
    }
}
