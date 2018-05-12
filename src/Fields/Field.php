<?php

namespace SimpleCrud\Fields;

use Latitude\QueryBuilder\Builder\CriteriaBuilder;
use Latitude\QueryBuilder\StatementInterface;
use SimpleCrud\FieldInterface;
use SimpleCrud\Table;
use function Latitude\QueryBuilder\field;
use function Latitude\QueryBuilder\identify;
use function Latitude\QueryBuilder\param;

class Field implements FieldInterface
{
    protected $table;
    protected $info;
    protected $config = [];

    public function __construct(Table $table, array $info)
    {
        $this->table = $table;
        $this->info = $info;
    }

    public function databaseValue($value)
    {
        if ($value === '' && $this->info['null']) {
            return;
        }

        return $value;
    }

    public function rowValue($value)
    {
        return $value;
    }

    public function getName(): string
    {
        return $this->info['name'];
    }

    public function identify(): StatementInterface
    {
        return identify(sprintf('%s.%s', $this->table->getName(), $this->getName()));
    }

    public function criteria(): CriteriaBuilder
    {
        return field(sprintf('%s.%s', $this->table->getName(), $this->getName()));
    }

    public function param($value): StatementInterface
    {
        if ($value instanceof StatementInterface) {
            return $value;
        }

        return param($value);
    }

    public function getConfig(string $name)
    {
        return isset($this->config[$name]) ? $this->config[$name] : null;
    }

    public function setConfig(string $name, $value)
    {
        $this->config[$name] = $value;
    }
}
