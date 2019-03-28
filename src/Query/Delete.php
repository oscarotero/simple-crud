<?php
declare(strict_types = 1);

namespace SimpleCrud\Query;

use SimpleCrud\Table;

final class Delete implements QueryInterface
{
    use Traits\Common;
    use Traits\HasRelatedWith;

    private $allowedMethods = [
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
