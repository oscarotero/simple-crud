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
        $this->init($table);

        $this->query = $this->builder
            ->insert($table->getName());

        if ($data) {
            $this->map($data);
        }
    }

    public function map(array $data): self
    {
        foreach ($data as $fieldName => &$value) {
            $value = $this->table->{$fieldName}->param($value);
        }

        $this->query->map($data);

        return $this;
    }

    public function run()
    {
        $this->__invoke();

        $id = $this->table->getDatabase()->lastInsertId();

        return $this->table->id->rowValue($id);
    }
}
