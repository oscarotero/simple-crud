<?php
declare(strict_types=1);

namespace SimpleCrud\Engine;

use SimpleCrud\SimpleCrud;

trait SchemeBuilderTrait
{
    private $db;

    public static function buildScheme(SimpleCrud $db): array
    {
        return (new static($db))->detect();
    }

    private function __construct(SimpleCrud $db)
    {
        $this->db = $db;
    }

    /**
     * Return the database scheme.
     */
    private function detect(): array
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
