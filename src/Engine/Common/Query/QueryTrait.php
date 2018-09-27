<?php
declare(strict_types = 1);

namespace SimpleCrud\Engine\Common\Query;

use Latitude\QueryBuilder\Query;
use PDOStatement;
use SimpleCrud\Engine\QueryInterface;
use SimpleCrud\Table;

trait QueryTrait
{
    private $table;
    private $builder;
    private $query;

    public static function create(Table $table, array $arguments): QueryInterface
    {
        return new static($table, ...$arguments);
    }

    private function init(Table $table)
    {
        $this->table = $table;
        $this->builder = $table->getDatabase()->query();
    }

    public function __toString()
    {
        return $this->query->getStatement();
    }

    public function getValues(): array
    {
        $values = $this->query->getBindValues();
        return array_column($values, 0);
    }

    public function compile(): Query
    {
        return $this->query->compile();
    }

    public function __call(string $name, array $arguments): self
    {
        $this->query->$name(...$arguments);
        return $this;
    }

    public function run()
    {
        $this->__invoke();
    }

    public function __invoke(): PDOStatement
    {
        return $this->query->perform();
    }
}
