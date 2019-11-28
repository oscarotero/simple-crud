<?php
declare(strict_types = 1);

namespace SimpleCrud\Queries;

use BadMethodCallException;
use PDOStatement;
use SimpleCrud\Table;

abstract class Query
{
    protected const ALLOWED_METHODS = [];

    protected $table;
    protected $query;

    public function getTable(): Table
    {
        return $this->table;
    }

    public function __call(string $name, array $arguments)
    {
        if (!in_array($name, static::ALLOWED_METHODS)) {
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

    public function get()
    {
        return $this->run();
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
