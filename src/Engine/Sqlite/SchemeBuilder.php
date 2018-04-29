<?php
declare(strict_types=1);

namespace SimpleCrud\Engine\Sqlite;

use function Latitude\QueryBuilder\field;
use SimpleCrud\Engine\SchemeBuilderTrait;
use SimpleCrud\Engine\SchemeBuilderInterface;
use PDO;

class SchemeBuilder implements SchemeBuilderInterface
{
    use SchemeBuilderTrait;

    protected function getTables(): array
    {
        $query = $this->db->query()
            ->select('name')
            ->from('sqlite_master')
            ->where(field('type')->in('table', 'view'))
            ->andWhere(field('name')->notEq('sqlite_sequence'))
            ->compile();

        return $this->db->execute($query)->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    protected function getTableFields(string $table): array
    {
        $result = $this->db->execute("pragma table_info(`{$table}`)")->fetchAll(PDO::FETCH_ASSOC);
        $fields = [];

        foreach ($result as $field) {
            $name = $field['name'];

            $fields[$name] = [
                'type' => strtolower($field['type']),
                'null' => ($field['notnull'] !== '1'),
                'default' => $field['dflt_value'],
                'unsigned' => null,
                'length' => null,
                'values' => null,
            ];
        }

        return $fields;
    }
}
