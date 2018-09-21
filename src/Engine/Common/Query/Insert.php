<?php
declare(strict_types = 1);

namespace SimpleCrud\Engine\Common\Query;

use SimpleCrud\Engine\QueryInterface;
use SimpleCrud\Table;

abstract class Insert implements QueryInterface
{
    use QueryTrait;

    public function __construct(Table $table, array $data = null)
    {
        $this->table = $table;

        $this->query = $table->getDatabase()
            ->insert()
            ->into($table->getName());

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
