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
        $query = $this->db->query()
            ->select('name')
            ->from('sqlite_master')
            ->where(field('type')->in('table', 'view'))
            ->andWhere(field('name')->notEq('sqlite_sequence'))
            ->compile();

        return $this->db
            ->execute($query->sql(), $query->params())
            ->fetchAll(PDO::FETCH_COLUMN, 0);
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
