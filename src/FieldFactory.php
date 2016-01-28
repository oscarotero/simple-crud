<?php

namespace SimpleCrud;

/**
 * Class to create instances of fields.
 */
class FieldFactory implements FieldFactoryInterface
{
    protected $cachedTypes = [];
    protected $namespaces = ['SimpleCrud\\Fields\\'];
    protected $defaultType = 'SimpleCrud\\Fields\\Field';
    protected $smartTypes = [
        'Decimal' => ['float'],
        'Integer' => ['tinyint', 'smallint', 'mediumint', 'bigint', 'year'],
    ];
    protected $smartNames = [
        'Boolean' => ['active'],
        'Datetime' => ['pubdate'],
        'Integer' => ['id'],
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
     * Set new smart names.
     *
     * @param string $name
     * @param string $type
     *
     * @return self
     */
    public function addSmartName($name, $type)
    {
        if (!isset($this->smartNames[$type])) {
            $this->smartNames[$type] = [$name];
        } else {
            $this->smartNames[$type][] = $name;
        }

        return $this;
    }

    /**
     * @see FieldFactoryInterface
     *
     * {@inheritdoc}
     */
    public function get(Entity $entity, array $config)
    {
        try {
            $type = $config['type'];

            if (($smartType = $this->getTypeByName($config['name']))) {
                $type = $smartType;
            }

            if ($type) {
                if (isset($this->cachedTypes[$type])) {
                    $class = $this->cachedTypes[$type];

                    return new $class($entity, $config);
                }

                $class = $this->getClass($type);

                if (!empty($class)) {
                    $this->cachedTypes[$type] = $class;

                    return new $class($entity, $config);
                }
            }

            $class = $this->cachedTypes[$type] = $this->defaultType;

            return new $class($entity, $config);
        } catch (\Exception $exception) {
            throw new SimpleCrudException("Error getting the '{$type}' field", 0, $exception);
        }
    }

    /**
     * Retrieves the field type to use.
     *
     * @param string $name
     *
     * @return string|null
     */
    protected function getTypeByName($name)
    {
        foreach ($this->smartNames as $type => $names) {
            if (in_array($name, $names, true)) {
                return $type;
            }
        }

        if (substr($name, -3) === '_id') {
            return 'Integer';
        }

        //Begin with is|in|has (for example: isActive, inHome, hasContent)
        if (preg_match('/^(is|in|has)[A-Z]/', $name)) {
            return 'Boolean';
        }

        //End with At (for example: publishedAt)
        if (preg_match('/[a-z]At$/', $name)) {
            return 'Datetime';
        }
    }

    /**
     * Retrieves the class name for a specific type if exists.
     *
     * @param string $type
     *
     * @return string|null
     */
    protected function getClass($type)
    {
        foreach ($this->smartTypes as $smartType => $types) {
            if (in_array($type, $types, true)) {
                $type = $smartType;
                break;
            }
        }

        $name = ucfirst($type);

        foreach ($this->namespaces as $namespace) {
            $class = $namespace.$name;

            if (class_exists($class)) {
                return $class;
            }
        }
    }
}
