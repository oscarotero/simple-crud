<?php
declare(strict_types=1);

namespace SimpleCrud\Engine\Sqlite;

use SimpleCrud\SimpleCrud;
use SimpleCrud\Engine\QueryFactoryBuilderInterface;
use Latitude\QueryBuilder\Engine\CommonEngine;
use Latitude\QueryBuilder\QueryFactory;

class QueryFactoryBuilder implements QueryFactoryBuilderInterface
{
    public static function buildQueryFactory(SimpleCrud $db): QueryFactory
    {
        return new QueryFactory(new CommonEngine());
    }
}
