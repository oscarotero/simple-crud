<?php
declare(strict_types = 1);

namespace SimpleCrud\Query;

use PDOStatement;

interface QueryInterface
{
    public function run();

    public function __invoke(): PDOStatement;
}
