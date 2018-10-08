<?php
declare(strict_types = 1);

namespace SimpleCrud\Query;

use SimpleCrud\Table;

final class Delete implements QueryInterface
{
    use Traits\Common;
    use Traits\HasWhere;
    use Traits\HasLimit;

    public function __construct(Table $table)
    {
        $this->query = $table->getDatabase()
            ->delete()
            ->from((string) $table);
    }
}
