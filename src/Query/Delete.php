<?php
declare(strict_types = 1);

namespace SimpleCrud\Query;

use SimpleCrud\Table;
use SimpleCrud\Events\CreateDeleteQuery;

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
        
        $eventDispatcher = $table->getEventDispatcher();

        if ($eventDispatcher) {
            $eventDispatcher->dispatch(new CreateDeleteQuery($this));
        }
    }
}
