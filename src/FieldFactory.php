<?php
declare(strict_types = 1);

namespace SimpleCrud;

/**
 * Class to create instances of fields.
 */
class FieldFactory implements FieldFactoryInterface
{
    protected $namespaces = ['SimpleCrud\\Fields\\'];
    protected $defaultType = 'Field';

    protected $nameMap = [
        'id' => 'Integer',
        'active' => 'Boolean',
        'pubdate' => 'Datetime',
    ];

    protected $regexMap = [
        //relation fields (post_id)
        '/_id$/' => 'Integer',

        //flags (isActive, inHome)
        '/^(is|has)[A-Z]/' => 'Boolean',

        //time related (createdAt, publishedAt)
        '/[a-z]At$/' => 'Datetime',
    ];

    protected $typeMap = [
        'bigint' => 'Integer',
        'boolean' => 'Boolean',
        'date' => 'Date',
        'datetime' => 'Datetime',
        'decimal' => 'Decimal',
        'float' => 'Decimal',
        'real' => 'Decimal', //sqlite
        'int' => 'Integer',
        'mediumint' => 'Integer',
        'set' => 'Set',
        'point' => 'Point',
        'smallint' => 'Integer',
        'tinyint' => 'Integer',
        'year' => 'Integer',
        'json' => 'Json',
    ];

    /**
     * Set the namespace for the fields classes.
     */
    public function addNamespace(string $namespace): self
    {
        array_unshift($this->namespaces, $namespace);

        return $this;
    }

    /**
     * Map names with field types.
     */
    public function mapNames(array $map): self
    {
        $this->nameMap = $map + $this->nameMap;

        return $this;
    }

    /**
     * Map names with field types using regexp.
     */
    public function mapRegex(array $map): self
    {
        $this->regexMap = $map + $this->regexMap;

        return $this;
    }

    /**
     * Map db field types with classes.
     */
    public function mapTypes(array $map): self
    {
        $this->typeMap = $map + $this->typeMap;

        return $this;
    }

    /**
     * @see FieldFactoryInterface
     *
     * {@inheritdoc}
     */
    public function get(Table $table, array $info): FieldInterface
    {
        $className = $this->getClassName($info['name'], $info['type']) ?: $this->defaultType;

        foreach ($this->namespaces as $namespace) {
            $class = $namespace.$className;

            if (class_exists($class)) {
                return new $class($table, $info);
            }
        }

        throw new SimpleCrudException("No field class found for '{$className}'");
    }

    /**
     * Get the field class name.
     */
    protected function getClassName(string $name, string $type): ?string
    {
        if (isset($this->nameMap[$name])) {
            return $this->nameMap[$name];
        }

        foreach ($this->regexMap as $regex => $class) {
            if (preg_match($regex, $name)) {
                return $class;
            }
        }

        if (isset($this->typeMap[$type])) {
            return $this->typeMap[$type];
        }

        return null;
    }
}
