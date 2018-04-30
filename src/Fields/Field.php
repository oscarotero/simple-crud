<?php

namespace SimpleCrud\Fields;

use Latitude\QueryBuilder\StatementInterface;
use SimpleCrud\Table;
use function Latitude\QueryBuilder\identify;
use function Latitude\QueryBuilder\param;

class Field
{
    protected $table;
    protected $name;
    protected $config = [];

    public function __construct(Table $table, string $name)
    {
        $this->table = $table;
        $this->name = $name;
    }

    public function databaseValue($value, array $data)
    {
        if ($value === '' && $this->getScheme()['null']) {
            return;
        }

        return $value;
    }

    public function rowValue($value, array $data = [])
    {
        return $value;
    }

    public function getScheme(): array
    {
        return $this->table->getScheme()['fields'][$this->name];
    }

    public function getIdentifier(): StatementInterface
    {
        return identify(sprintf('%s.%s', $this->table->getName(), $this->name));
    }

    public function valueToParam($value): StatementInterface
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

    public function setConfig(string $name, $value): self
    {
        $this->config[$name] = $value;

        return $this;
    }
}
