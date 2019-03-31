<?php
declare(strict_types = 1);

namespace SimpleCrud;

use SimpleCrud\Fields\Boolean;
use SimpleCrud\Fields\Date;
use SimpleCrud\Fields\Datetime;
use SimpleCrud\Fields\Decimal;
use SimpleCrud\Fields\Field;
use SimpleCrud\Fields\FieldInterface;
use SimpleCrud\Fields\Integer;
use SimpleCrud\Fields\Json;
use SimpleCrud\Fields\Point;
use SimpleCrud\Fields\Set;

/**
 * Class to create instances of fields.
 */
final class FieldFactory implements FieldFactoryInterface
{
    private $defaultType = Field::class;
    private $fields = [
        Boolean::class => [
            'names' => ['active', '/^(is|has)[A-Z]/'],
            'types' => ['boolean'],
            'config' => [],
        ],
        Date::class => [
            'names' => [],
            'types' => ['date'],
            'config' => [],
        ],
        Datetime::class => [
            'names' => ['pubdate', '/[a-z]At$/'],
            'types' => ['datetime'],
            'config' => [],
        ],
        Decimal::class => [
            'names' => [],
            'types' => ['decimal', 'float', 'real'],
            'config' => [],
        ],
        Field::class => [
            'names' => [],
            'types' => [],
            'config' => [],
        ],
        Integer::class => [
            'names' => ['id','/_id$/'],
            'types' => ['bigint', 'int', 'mediumint', 'smallint', 'tinyint', 'year'],
            'config' => [],
        ],
        Json::class => [
            'names' => [],
            'types' => ['json'],
            'config' => [],
        ],
        Point::class => [
            'names' => [],
            'types' => ['point'],
            'config' => [],
        ],
        Set::class => [
            'names' => [],
            'types' => ['set'],
            'config' => [],
        ],
    ];

    public function defineField(string $className, array $definition): self
    {
        if (isset($this->fields[$className])) {
            $this->fields[$className] = $definition;
            return $this;
        }

        $this->fields = [$className => $definition] + $this->fields;

        return $this;
    }

    public function getFieldDefinition(string $className): ?array
    {
        return $this->fields[$className] ?? null;
    }

    /**
     * @see FieldFactoryInterface
     *
     * {@inheritdoc}
     */
    public function get(Table $table, array $info): FieldInterface
    {
        $className = $this->getClassName($info['name'], $info['type']);

        if (class_exists($className)) {
            $field = new $className($table, $info);
            $config = $this->fields[$className]['config'] ?? [];

            foreach ($config as $name => $value) {
                $field->setConfig($name, $value);
            }

            return $field;
        }

        throw new SimpleCrudException("No field class found for '{$className}'");
    }

    /**
     * Get the field class name.
     */
    private function getClassName(string $name, string $type): ?string
    {
        foreach ($this->fields as $className => $definition) {
            foreach ($definition['names'] as $defName) {
                if ($defName === $name || ($defName[0] === '/' && preg_match($defName, $name))) {
                    return $className;
                }
            }

            if (in_array($type, $definition['types'])) {
                return $className;
            }
        }

        return $this->defaultType;
    }
}
