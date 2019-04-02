<?php
declare(strict_types = 1);

namespace SimpleCrud\Queries;

use SimpleCrud\Table;

final class Delete extends Query
{
    use Traits\HasRelatedWith;

    protected const ALLOWED_METHODS = [
        'setFlag',
        'where',
        'orWhere',
        'catWhere',
        'orderBy',
        'limit',
        'offset',
    ];

    public function __construct(Table $table)
    {
        $this->table = $table;

        $this->query = $table->getDatabase()
            ->delete()
            ->from((string) $table);
    }
}
