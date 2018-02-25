<?php

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
     *
     * @param string $namespace
     *
     * @return self
     */
    public function addNamespace($namespace)
    {
        array_unshift($this->namespaces, $namespace);

        return $this;
    }

    /**
     * Map names with field types.
     *
     * @param array $map
     *
     * @return self
     */
    public function mapNames(array $map)
    {
        $this->nameMap = $map + $this->nameMap;

        return $this;
    }

    /**
     * Map names with field types using regexp.
     *
     * @param array $map
     *
     * @return self
     */
    public function mapRegex(array $map)
    {
        $this->regexMap = $map + $this->regexMap;

        return $this;
    }

    /**
     * Map db field types with classes.
     *
     * @param array $map
     *
     * @return self
     */
    public function mapTypes(array $map)
    {
        $this->typeMap = $map + $this->typeMap;

        return $this;
    }

    /**
     * @see FieldFactoryInterface
     *
     * {@inheritdoc}
     */
    public function get(Table $table, $name)
    {
        $scheme = $table->getScheme()['fields'];

        if (!isset($scheme[$name])) {
            throw new SimpleCrudException(sprintf('The field "%s" does not exist in the table "%s"', $name, $table->getName()));
        }

        $className = $this->getClassName($name, $scheme[$name]['type']) ?: $this->defaultType;

        foreach ($this->namespaces as $namespace) {
            $class = $namespace.$className;

            if (class_exists($class)) {
                return new $class($table, $name);
            }
        }

        throw new SimpleCrudException("No field class found for '{$className}'");
    }

    /**
     * Get the field class name.
     *
     * @param string $name
     * @param string $type
     *
     * @return string|null
     */
    protected function getClassName($name, $type)
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
    }
}
