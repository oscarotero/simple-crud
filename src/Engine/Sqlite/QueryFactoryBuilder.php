<?php
declare(strict_types = 1);

namespace SimpleCrud\Engine\Sqlite;

use Latitude\QueryBuilder\Engine\CommonEngine;
use Latitude\QueryBuilder\QueryFactory;
use SimpleCrud\Engine\QueryFactoryBuilderInterface;
use SimpleCrud\SimpleCrud;

class QueryFactoryBuilder implements QueryFactoryBuilderInterface
{
    public static function buildQueryFactory(SimpleCrud $db): QueryFactory
    {
        return new QueryFactory(new CommonEngine());
    }
}
