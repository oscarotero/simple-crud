<?php
declare(strict_types = 1);

namespace SimpleCrud\Events;

use SimpleCrud\Row;

final class BeforeSaveRow
{
    private $row;

    public function __construct(Row $row)
    {
        $this->row = $row;
    }

    public function getRow(): Row
    {
        return $this->row;
    }
}
