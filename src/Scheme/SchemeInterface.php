<?php
declare(strict_types = 1);

namespace SimpleCrud\Scheme;

interface SchemeInterface
{
    /**
     * Return the names of all tables
     */
    public function getTables(): array;

    /**
     * Return the field info of a table using an array with the following keys:
     * name, type, null, default, unsigned, length and values
     */
    public function getTableFields(string $table): array;

    public function toArray(): array;
}
