<?php
declare(strict_types = 1);

namespace SimpleCrud\Query;

use SimpleCrud\Table;
use SimpleCrud\Events\CreateInsertQuery;

final class Insert implements QueryInterface
{
    use Traits\Common;

    private $allowedMethods = [
        'setFlag',
        'set',
    ];

    public function __construct(Table $table, array $data = [])
    {
        $this->table = $table;

        $this->query = $table->getDatabase()
            ->insert()
            ->into((string) $table);

        foreach ($data as $fieldName => $value) {
            $this->table->{$fieldName}->insert($this->query, $value);
        }

        $eventDispatcher = $table->getEventDispatcher();

        if ($eventDispatcher) {
            $eventDispatcher->dispatch(new CreateInsertQuery($this));
        }
    }

    public function run()
    {
        $this->__invoke();

        $id = $this->table->getDatabase()->lastInsertId();

        return $this->table->id->format($id);
    }
}
