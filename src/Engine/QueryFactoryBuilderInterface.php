<?php
declare(strict_types = 1);

namespace SimpleCrud\Engine;

use Latitude\QueryBuilder\QueryFactory;
use SimpleCrud\SimpleCrud;

interface QueryFactoryBuilderInterface
{
    public static function buildQueryFactory(SimpleCrud $db): QueryFactory;
}
