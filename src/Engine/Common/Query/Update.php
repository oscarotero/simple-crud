<?php
declare(strict_types = 1);

namespace SimpleCrud\Engine\Common\Query;

use SimpleCrud\Engine\QueryInterface;
use SimpleCrud\Table;

abstract class Update implements QueryInterface
{
    use QueryTrait;
    use WhereTrait;

    public function __construct(Table $table, array $data = null)
    {
        $this->init($table);

        $this->query = $this->builder
            ->update($table->getName());

        if ($data) {
            $this->set($data);
        }
    }

    public function set(array $data): self
    {
        foreach ($data as $fieldName => &$value) {
            $value = $this->table->{$fieldName}->param($value);
        }

        $this->query->set($data);

        return $this;
    }
}
