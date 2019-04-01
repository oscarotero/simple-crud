<?php
declare(strict_types = 1);

namespace SimpleCrud\Query;

use SimpleCrud\Table;

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
    }

    public function run()
    {
        $this->__invoke();

        $id = $this->table->getDatabase()->lastInsertId();

        return $this->table->id->format($id);
    }
}
