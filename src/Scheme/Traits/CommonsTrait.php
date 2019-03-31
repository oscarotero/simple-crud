<?php
declare(strict_types = 1);

namespace SimpleCrud\Scheme\Traits;

use PDO;

trait CommonsTrait
{
    private $scheme;
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @see SchemeInterface
     */
    public function toArray(): array
    {
        $array = [];

        foreach ($this->getTables() as $table) {
            $array[$table] = $this->getTableFields($table);
        }

        return $array;
    }

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
