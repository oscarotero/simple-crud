<?php

namespace SimpleCrud;

use JsonSerializable;

/**
 * Represent a null value.
 * Used to allow chaining even when the result is null.
 */
class NullValue implements JsonSerializable
{
    /**
     * Magic method to return properties or load them automatically.
     *
     * @param string $name
     */
    public function __get($name)
    {
        return new self();
    }

    /**
     * Magic method to store properties.
     *
     * @param string $name
     * @param mixed  $value
     */
    public function __set($name, $value)
    {
        throw new \Exception('No values can be stored in a RowNull');
    }

    /**
     * Magic method to check if a property is defined or is empty.
     *
     * @param string $name Property name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return false;
    }

    /**
     * Magic method to print the row values (and subvalues).
     *
     * @return string
     */
    public function __toString()
    {
        return '';
    }

    /**
     * @see JsonSerializable
     *
     * @return mixed
     */
    public function jsonSerialize()
    {
        return;
    }
}
