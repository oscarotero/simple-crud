<?php
declare(strict_types = 1);

namespace SimpleCrud\Scheme;

use PDO;

final class Sqlite implements SchemeInterface
{
    use Traits\CommonsTrait;

    protected function loadTables(): array
    {
        return $this->pdo->query(
            "select name from sqlite_master where type in ('table', 'view') and name != 'sqlite_sequence'"
        )->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    protected function loadTableFields(string $table): array
    {
        $result = $this->pdo->query("pragma table_info(`{$table}`)")->fetchAll(PDO::FETCH_ASSOC);

        return array_map(
            function ($field) {
                return [
                    'name' => $field['name'],
                    'type' => strtolower($field['type']),
                    'null' => ($field['notnull'] !== '1'),
                    'default' => $field['dflt_value'],
                    'unsigned' => null,
                    'length' => null,
                    'values' => null,
                ];
            },
            $result
        );
    }
}
