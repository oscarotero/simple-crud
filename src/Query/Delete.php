<?php
declare(strict_types = 1);

namespace SimpleCrud\Query;

use SimpleCrud\Table;

final class Delete implements QueryInterface
{
    use Traits\CommonsTrait;
    use Traits\WhereTrait;

    public function __construct(Table $table)
    {
        $this->query = $table->getDatabase()
            ->delete()
            ->from((string) $table);
    }
}
