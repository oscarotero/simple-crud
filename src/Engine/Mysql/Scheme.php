<?php
declare(strict_types = 1);

namespace SimpleCrud\Engine\Mysql;

use PDO;
use SimpleCrud\Engine\Common\Scheme as BaseScheme;
use SimpleCrud\Engine\SchemeInterface;
use function Latitude\QueryBuilder\field;

class Scheme extends BaseScheme implements SchemeInterface
{
    protected function getTables(): array
    {
        return $this->db->execute('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    protected function getTableFields(string $table): array
    {
        $result = $this->db->execute("DESCRIBE `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
        $fields = [];

        foreach ($result as $field) {
            $name = $field['Field'];

            preg_match('#^(\w+)(\((.+)\))?( unsigned)?#', $field['Type'], $matches);

            $config = [
                'type' => $matches[1],
                'null' => ($field['Null'] === 'YES'),
                'default' => $field['Default'],
                'unsigned' => !empty($matches[4]),
                'length' => null,
                'values' => null,
            ];

            switch ($config['type']) {
                case 'enum':
                case 'set':
                    $config['values'] = explode(',', $matches[3]);
                    break;
                default:
                    if (!isset($matches[3])) {
                        $config['length'] = null;
                    } elseif (strpos($matches[3], ',')) {
                        $config['length'] = floatval(str_replace(',', '.', $matches[3]));
                    } else {
                        $config['length'] = intval($matches[3]);
                    }
            }

            $fields[$name] = $config;
        }

        return $fields;
    }
}
