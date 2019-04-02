<?php
declare(strict_types = 1);

namespace SimpleCrud\Fields;

use Atlas\Query\Insert;
use Atlas\Query\Select;
use Atlas\Query\Update;
use SimpleCrud\Table;

class Field
{
    protected $table;
    protected $info;
    protected $config = [];

    public function __construct(Table $table, array $info)
    {
        $this->table = $table;
        $this->info = $info;
    }

    public function getName(): string
    {
        return $this->info['name'];
    }

    public function __toString()
    {
        return sprintf('%s.`%s`', $this->table, $this->info['name']);
    }

    public function select(Select $query)
    {
        $query->columns((string) $this);
    }

    public function insert(Insert $query, $value)
    {
        $query->column($this->getName(), $this->formatToDatabase($value));
    }

    public function update(Update $query, $value)
    {
        $query->column($this->getName(), $this->formatToDatabase($value));
    }

    public function format($value)
    {
        if ($value === '' && $this->info['null']) {
            return;
        }

        return $value;
    }

    public function getConfig(string $name)
    {
        return isset($this->config[$name]) ? $this->config[$name] : null;
    }

    public function setConfig(string $name, $value)
    {
        $this->config[$name] = $value;
    }

    protected function formatToDatabase($value)
    {
        return $this->format($value);
    }
}
