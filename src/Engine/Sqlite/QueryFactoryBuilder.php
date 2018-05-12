<?php
declare(strict_types = 1);

namespace SimpleCrud\Engine\Sqlite;

use Latitude\QueryBuilder\Engine\CommonEngine;
use Latitude\QueryBuilder\QueryFactory;
use SimpleCrud\Database;
use SimpleCrud\Engine\QueryFactoryBuilderInterface;

class QueryFactoryBuilder implements QueryFactoryBuilderInterface
{
    public static function buildQueryFactory(Database $db): QueryFactory
    {
        return new QueryFactory(new CommonEngine());
    }
}
