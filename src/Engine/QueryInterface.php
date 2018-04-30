<?php
declare(strict_types = 1);

namespace SimpleCrud\Engine;

use Latitude\QueryBuilder\Query;
use PDOStatement;
use SimpleCrud\Table;

interface QueryInterface
{
    public static function create(Table $table, array $arguments): QueryInterface;

    public function compile(): Query;

    public function run();

    public function __invoke(): PDOStatement;
}
