<?php
declare(strict_types = 1);

namespace SimpleCrud\Engine\Sqlite;

use PDO;
use SimpleCrud\Database;
use SimpleCrud\Engine\Common\Scheme as BaseScheme;
use function Latitude\QueryBuilder\field;

class Scheme extends BaseScheme
{
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    protected function loadTables(): array
    {
        return $this->db->select()
            ->columns('name')
            ->from('sqlite_master')
            ->whereEquals([
                'type' => ['table', 'view'],
            ])
            ->andWhere('name != "sqlite_sequence"')
            ->fetchColumn();
    }

    protected function loadTableFields(string $table): array
    {
        $result = $this->db->execute("pragma table_info(`{$table}`)")->fetchAll(PDO::FETCH_ASSOC);

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
