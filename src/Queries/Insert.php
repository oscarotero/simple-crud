<?php
declare(strict_types = 1);

namespace SimpleCrud\Queries;

use SimpleCrud\Table;

final class Insert extends Query
{
    protected const ALLOWED_METHODS = [
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

    public function orIgnore(): self
    {
        $engine = $this->table->getDatabase()->getConnection()->getDriverName();

        switch ($engine) {
            case 'mysql':
                $this->query->setFlag('IGNORE');
                break;
            case 'sqlite':
                $this->query->setFlag('OR IGNORE');
                break;
        }

        return $this;
    }

    public function run()
    {
        $this->__invoke();

        $id = $this->table->getDatabase()->lastInsertId();

        return $this->table->id->format($id);
    }
}
