<?php
declare(strict_types = 1);

namespace SimpleCrud\Engine\Common\Query;

use PDO;
use SimpleCrud\Engine\QueryInterface;
use SimpleCrud\Row;
use SimpleCrud\Table;

abstract class Select implements QueryInterface
{
    use QueryTrait;
    use WhereTrait;

    private $one;

    public function __construct(Table $table)
    {
        $this->init($table);

        $fields = array_map(
            function ($field) {
                return $field->identify();
            },
            array_values($table->getFields())
        );

        $this->query = $this->builder
            ->select(...$fields)
            ->from($table->getName());
    }

    public function one(): self
    {
        $this->one = true;
        $this->query->limit(1);

        return $this;
    }

    public function page(int $page, int $length): self
    {
        if ($page < 1) {
            $page = 1;
        }

        $this->query->limit($length);
        $this->query->offset(($page * $length) - $length);

        return $this;
    }

    public function run()
    {
        $statement = $this->__invoke();
        $statement->setFetchMode(PDO::FETCH_ASSOC);

        if ($this->one) {
            $data = $statement->fetch();

            return $data ? $this->table->create($data, true) : null;
        }

        $rows = array_map(
            function ($row): Row {
                return $this->table->create($row, true);
            },
            $statement->fetchAll()
        );

        return $this->table->createCollection($rows);
    }
}
