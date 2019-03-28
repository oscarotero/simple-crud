<?php
declare(strict_types = 1);

namespace SimpleCrud\Query\Traits;

use BadMethodCallException;
use PDOStatement;
use SimpleCrud\Query\QueryInterface;
use SimpleCrud\Table;

trait Common
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

    public function __call(string $name, array $arguments)
    {
        if (!in_array($name, $this->allowedMethods)) {
            throw new BadMethodCallException(sprintf('The method "%s" is not valid', $name));
        }

        $this->query->{$name}(...$arguments);

        return $this;
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

    public function run()
    {
        $this->__invoke();
    }

    public function __invoke(): PDOStatement
    {
        return $this->query->perform();
    }
}
