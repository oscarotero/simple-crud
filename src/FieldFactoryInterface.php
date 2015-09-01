<?php
namespace SimpleCrud;

/**
 * Interface used by the FieldFactory class
 */
interface FieldFactoryInterface
{
    /**
     * Creates a new instance of a Field
     *
     * @param string $name
     * @param string $type
     * 
     * @throws SimpleCrudException
     *
     * @return FieldInterface
     */
    public function get($name, $type);
}
