<?php
declare(strict_types = 1);

namespace SimpleCrud;

use SimpleCrud\Fields\FieldInterface;
use SimpleCrud\Fields\Field;
use SimpleCrud\Fields\Integer;
use SimpleCrud\Fields\Boolean;
use SimpleCrud\Fields\Datetime;
use SimpleCrud\Fields\Date;
use SimpleCrud\Fields\Decimal;
use SimpleCrud\Fields\Set;
use SimpleCrud\Fields\Point;
use SimpleCrud\Fields\Json;

/**
 * Class to create instances of fields.
 */
final class FieldFactory implements FieldFactoryInterface
{
    private $defaultType = Field::class;
    private $fields = [
        Integer::class => [
            'names' => ['id'],
            'regex' => ['/_id$/'],
            'types' => ['bigint', 'int', 'mediumint', 'smallint', 'tinyint', 'year'],
        ],
        Boolean::class => [
            'names' => ['active'],
            'regex' => ['/^(is|has)[A-Z]/'],
            'types' => ['boolean'],
        ],
        Datetime::class => [
            'names' => ['pubdate'],
            'regex' => ['/[a-z]At$/'],
            'types' => ['datetime']
        ],
        Date::class => [
            'names' => [],
            'regex' => [],
            'types' => ['date']
        ],
        Decimal::class => [
            'names' => [],
            'regex' => [],
            'types' => ['decimal', 'float', 'real']
        ],
        Set::class => [
            'names' => [],
            'regex' => [],
            'types' => ['set']
        ],
        Point::class => [
            'names' => [],
            'regex' => [],
            'types' => ['point']
        ],
        Json::class => [
            'names' => [],
            'regex' => [],
            'types' => ['json']
        ],
    ];

    public function defineField(string $className, array $definition): self
    {
        if (isset($this->fields[$className])) {
            $this->fields[$className] = $definition;
            return $this;
        }

        $this->fields = [$className => $values] + $this->fields;

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
            return new $className($table, $info);
        }

        throw new SimpleCrudException("No field class found for '{$className}'");
    }

    /**
     * Get the field class name.
     */
    private function getClassName(string $name, string $type): ?string
    {
        foreach ($this->fields as $className => $definition) {
            if (in_array($name, $definition['names'])) {
                return $className;
            }

            foreach ($definition['regex'] as $regex) {
                if (preg_match($regex, $name)) {
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
