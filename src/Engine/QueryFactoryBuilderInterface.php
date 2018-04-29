<?php
declare(strict_types=1);

namespace SimpleCrud\Engine;

use SimpleCrud\SimpleCrud;
use Latitude\QueryBuilder\QueryFactory;

interface QueryFactoryBuilderInterface
{
    public static function buildQueryFactory(SimpleCrud $db): QueryFactory;
}
