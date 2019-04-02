<?php
declare(strict_types = 1);

namespace SimpleCrud\Events;

use SimpleCrud\Queries\Select;

final class CreateSelectQuery
{
    private $query;

    public function __construct(Select $query)
    {
        $this->query = $query;
    }

    public function getQuery(): Select
    {
        return $this->query;
    }
}
