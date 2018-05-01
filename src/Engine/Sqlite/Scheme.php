<?php
declare(strict_types = 1);

namespace SimpleCrud\Engine\Sqlite;

use PDO;
use SimpleCrud\Engine\Common\Scheme as BaseScheme;
use SimpleCrud\Engine\SchemeInterface;
use function Latitude\QueryBuilder\field;

class Scheme extends BaseScheme implements SchemeInterface
{
    protected function getTables(): array
    {
        $query = $this->db->query()
            ->select('name')
            ->from('sqlite_master')
            ->where(field('type')->in('table', 'view'))
            ->andWhere(field('name')->notEq('sqlite_sequence'))
            ->compile();

        return $this->db->execute($query->sql(), $query->params())->fetchAll(PDO::FETCH_COLUMN, 0);
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
