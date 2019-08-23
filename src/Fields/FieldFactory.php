<?php
declare(strict_types = 1);

namespace SimpleCrud\Fields;

use SimpleCrud\Table;

/**
 * Class to create instances of a field.
 */
final class FieldFactory implements FieldFactoryInterface
{
    private $className;
    private $types = [];
    private $names = [];
    private $config = [];

    public function __construct(string $className, array $types = [], array $names = [], array $config = [])
    {
        $this->className = $className;
        $this->types = $types;
        $this->names = $names;
        $this->config = $config;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function addTypes(string ...$types): self
    {
        $this->types = array_merge($this->types, $types);
        return $this;
    }

    public function getTypes(): array
    {
        return $this->types;
    }

    public function addNames(string ...$names): self
    {
        $this->names = array_merge($this->names, $names);
        return $this;
    }

    public function getNames(): array
    {
        return $this->names;
    }

    public function addConfig(array $config): self
    {
        $this->config = $config + $this->config;
        return $this;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function create(Table $table, array $info): ?Field
    {
        if (!$this->matches($info['name'], $info['type'])) {
            return null;
        }

        $className = $this->className;
        $field = new $className($table, $info);

        foreach ($this->config as $name => $value) {
            $field->setConfig($name, $value);
        }

        return $field;
    }

    private function matches(string $fieldName, string $fieldType): bool
    {
        foreach ($this->names as $name) {
            if ($fieldName === $name || ($name[0] === '/' && preg_match($name, $fieldName))) {
                return true;
            }
        }

        return in_array($fieldType, $this->types);
    }
}
