<?php
declare(strict_types = 1);

namespace SimpleCrud\Query;

use SimpleCrud\Table;

final class Insert implements QueryInterface
{
    use Traits\CommonsTrait;

    public function __construct(Table $table, array $data = null)
    {
        $this->table = $table;

        $this->query = $table->getDatabase()
            ->insert()
            ->into((string) $table);

        if ($data) {
            $this->columns($data);
        }
    }

    public function columns(array $data): self
    {
        foreach ($data as $fieldName => $value) {
            $this->table->{$fieldName}->insert($this->query, $value);
        }

        return $this;
    }

    public function run()
    {
        $this->__invoke();

        $id = $this->table->getDatabase()->lastInsertId();

        return $this->table->id->rowValue($id);
    }
}
