<?php
declare(strict_types = 1);

namespace SimpleCrud;

/**
 * Interface used by the FieldFactory class.
 */
interface FieldFactoryInterface
{
    /**
     * Creates a new instance of a Field.
     *
     * @throws SimpleCrudException
     */
    public function get(Table $table, array $info): FieldInterface;
}
