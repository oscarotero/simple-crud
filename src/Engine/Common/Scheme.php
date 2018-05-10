<?php
declare(strict_types = 1);

namespace SimpleCrud\Engine\Common;

use SimpleCrud\Engine\SchemeInterface;
use SimpleCrud\SimpleCrud;
use SimpleCrud\Table;

abstract class Scheme
{
    protected $db;
    protected $scheme;

    public function __construct(SimpleCrud $db)
    {
        $this->db = $db;
    }

    public function toArray(): array
    {
        if ($this->scheme) {
            return $this->scheme;
        }

        return $this->scheme = $this->detectScheme();
    }

    public function getRelation(Table $table1, Table $table2): ?int
    {
        if ($table1->getJoinField($table2)) {
            return SchemeInterface::HAS_ONE;
        }

        if ($table2->getJoinField($table1)) {
            return SchemeInterface::HAS_MANY;
        }

        if ($table1->getJoinTable($table2)) {
            return SchemeInterface::HAS_MANY_TO_MANY;
        }
    }

    protected function detectScheme(): array
    {
        $scheme = [];

        foreach ($this->getTables() as $table) {
            $scheme[$table] = [
                'fields' => $this->getTableFields($table),
                'relations' => [],
            ];
        }

        foreach ($scheme as $table => &$info) {
            $foreingKey = "{$table}_id";

            foreach ($scheme as $relTable => &$relInfo) {
                if (isset($relInfo['fields'][$foreingKey])) {
                    $info['relations'][$relTable] = [SchemeInterface::HAS_MANY, $foreingKey];

                    if ($table === $relTable) {
                        $relInfo['relations'][$table] = [SchemeInterface::HAS_MANY, $foreingKey];
                    } else {
                        $relInfo['relations'][$table] = [SchemeInterface::HAS_ONE, $foreingKey];
                    }
                    continue;
                }

                if ($table < $relTable) {
                    $bridge = "{$table}_{$relTable}";
                } else {
                    $bridge = "{$relTable}_{$table}";
                }

                if (isset($scheme[$bridge])) {
                    $relForeingKey = "{$relTable}_id";

                    if (isset($scheme[$bridge]['fields'][$foreingKey]) && isset($scheme[$bridge]['fields'][$relForeingKey])) {
                        $info['relations'][$relTable] = [SchemeInterface::HAS_MANY_TO_MANY, $bridge, $foreingKey, $relForeingKey];
                        $relInfo['relations'][$table] = [SchemeInterface::HAS_MANY_TO_MANY, $bridge, $relForeingKey, $foreingKey];
                    }
                }
            }
        }

        return $scheme;
    }

    /**
     * Return all tables.
     */
    abstract protected function getTables(): array;

    /**
     * Return the scheme of a table.
     */
    abstract protected function getTableFields(string $table): array;
}
