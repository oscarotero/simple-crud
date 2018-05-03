<?php
declare(strict_types = 1);

namespace SimpleCrud\Engine\Common;

use SimpleCrud\Engine\SchemeInterface;
use SimpleCrud\Row;
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
        if (isset($table1->{$table2->getForeignKey()})) {
            return SchemeInterface::HAS_ONE;
        }

        if (isset($table2->{$table1->getForeignKey()})) {
            return SchemeInterface::HAS_MANY;
        }

        $bridge = $this->getManyToManyTableName($table1, $table2);

        if ($this->db->$bridge) {
            $bridge = $this->db->$bridge;

            if (isset($bridge->{$table1->getForeignKey()}) && isset($bridge->{$table2->getForeignKey()})) {
                return SchemeInterface::HAS_MANY_TO_MANY;
            }
        }
    }

    public function relate(Row $row1, Row $row2)
    {
        $table1 = $row1->getTable();
        $table2 = $row2->getTable();

        switch ($this->getRelation($table1, $table2)) {
            case SchemeInterface::HAS_ONE:
                $row1->{$table2->getForeignKey()} = $row2->id;
                break;
            case SchemeInterface::HAS_MANY:
                $row2->{$table1->getForeignKey()} = $row1->id;
                break;
        }
    }

    public function getManyToManyTableName(Table $table1, Table $table2): string
    {
        $name1 = $table1->getName();
        $name2 = $table2->getName();

        return $name1 < $name2 ? "{$name1}_{$name2}" : "{$name2}_{$name1}";
    }

    public function getManyToManyTable(Table $table1, Table $table2): Table
    {
        $name = $this->getManyToManyTableName($table1, $table2);

        return $this->db->{$name};
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
