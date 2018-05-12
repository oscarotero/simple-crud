<?php
declare(strict_types = 1);

namespace SimpleCrud\Engine;

use Latitude\QueryBuilder\QueryFactory;
use SimpleCrud\Database;

interface QueryFactoryBuilderInterface
{
    public static function buildQueryFactory(Database $db): QueryFactory;
}
