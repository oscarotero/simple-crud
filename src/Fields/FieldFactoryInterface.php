<?php
declare(strict_types = 1);

namespace SimpleCrud\Fields;

use SimpleCrud\Table;

/**
 * Interface used by the FieldFactory class.
 */
interface FieldFactoryInterface
{
    public function create(Table $table, array $info): ?Field;
}
