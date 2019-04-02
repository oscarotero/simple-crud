<?php
declare(strict_types = 1);

namespace SimpleCrud\Events;

use SimpleCrud\Queries\Update;

class CreateUpdateQuery
{
    private $query;

    public function __construct(Update $query)
    {
        $this->query = $query;
    }

    public function getQuery(): Update
    {
        return $this->query;
    }
}
