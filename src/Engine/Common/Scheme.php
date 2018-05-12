<?php
declare(strict_types = 1);

namespace SimpleCrud\Engine\Common;

use SimpleCrud\SchemeInterface;

abstract class Scheme implements SchemeInterface
{
    private $scheme;

    /**
     * @see SchemeInterface
     */
    public function getTables(): array
    {
        if (!is_array($this->scheme)) {
            $this->scheme = [];

            foreach ($this->loadTables() as $table) {
                $this->scheme[$table] = null;
            }
        }

        return array_keys($this->scheme);
    }

    /**
     * @see SchemeInterface
     */
    public function getTableFields(string $table): array
    {
        if (!is_array($this->scheme[$table])) {
            $this->scheme[$table] = $this->loadTableFields($table);
        }

        return $this->scheme[$table];
    }

    /**
     * Return all tables.
     */
    abstract protected function loadTables(): array;

    /**
     * Return the scheme of a table.
     */
    abstract protected function loadTableFields(string $table): array;
}
