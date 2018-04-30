<?php
declare(strict_types = 1);

namespace SimpleCrud\Engine\Mysql;

use Latitude\QueryBuilder\Engine\MySqlEngine;
use Latitude\QueryBuilder\QueryFactory;
use SimpleCrud\Engine\QueryFactoryBuilderInterface;
use SimpleCrud\SimpleCrud;

class QueryFactoryBuilder implements QueryFactoryBuilderInterface
{
    public static function buildQueryFactory(SimpleCrud $db): QueryFactory
    {
        return new QueryFactory(new MySqlEngine());
    }
}
