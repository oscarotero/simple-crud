<?php
declare(strict_types = 1);

namespace SimpleCrud\Query;

use PDOStatement;
use SimpleCrud\Table;

interface QueryInterface
{
    public static function create(Table $table, array $arguments): QueryInterface;

    public function run();

    public function __invoke(): PDOStatement;
}
