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
        $this->table = $table;

        $this->query = $table->getDatabase()
            ->update()
            ->table($table->getName());

        if ($data) {
            $this->columns($data);
        }
    }

    public function columns(array $data): self
    {
        foreach ($data as $fieldName => $value) {
            $this->table->{$fieldName}->update($this->query, $value);
        }

        return $this;
    }
}
