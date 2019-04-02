<?php
declare(strict_types = 1);

namespace SimpleCrud\Events;

use SimpleCrud\Queries\Insert;

final class CreateInsertQuery
{
    private $query;

    public function __construct(Insert $query)
    {
        $this->query = $query;
    }

    public function getQuery(): Insert
    {
        return $this->query;
    }
}
