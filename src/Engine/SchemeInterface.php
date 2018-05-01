<?php
declare(strict_types = 1);

namespace SimpleCrud\Engine;

use SimpleCrud\Table;

interface SchemeInterface
{
    const HAS_ONE = 1;
    const HAS_MANY = 2;
    const HAS_MANY_TO_MANY = 4;

    public function toArray(): array;

    public function getRelation(Table $table1, Table $table2): ?int;

    public function getManyToManyTableName(Table $table1, Table $table2): string;
}
