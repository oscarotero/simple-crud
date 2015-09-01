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
    protected $smartNames = [
        'Boolean' => ['active'],
        'Datetime' => ['pubdate', 'createdAt', 'updatedAt'],
        'Integer' => ['id'],
    ];

    /**
     * Set the namespace for the fields classes
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
     * Set new smart names
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
    public function get($name, $type)
    {
        try {
            if (empty($type)) {
                $type = $this->getTypeByName($name);
            }

            if ($type) {
                if (isset($this->cachedTypes[$type])) {
                    $class = $this->cachedTypes[$type];

                    return new $class();
                }

                $class = $this->getClass($type);

                if (!empty($class)) {
                    $this->cachedTypes[$type] = $class;

                    return new $class();
                }
            }

            $class = $this->cachedTypes[$type] = $this->defaultType;

            return new $class();
        } catch (Exception $exception) {
            throw new SimpleCrudException("Error getting the '{$type}' field", 0, $exception);
        }
    }

    /**
     * Retrieves the field type to use
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
    }

    /**
     * Retrieves a class name if exists
     *
     * @param string $name
     *
     * @return string|null
     */
    protected function getClass($name)
    {
        $name = ucfirst($name);

        foreach ($this->namespaces as $namespace) {
            $class = $namespace.$name;

            if (class_exists($class)) {
                return $class;
            }
        }
    }
}
