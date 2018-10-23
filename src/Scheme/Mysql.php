<?php
declare(strict_types = 1);

namespace SimpleCrud\Scheme;

use PDO;

final class Mysql implements SchemeInterface
{
    use Traits\CommonsTrait;

    protected function loadTables(): array
    {
        return $this->pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    protected function loadTableFields(string $table): array
    {
        $result = $this->pdo->query("DESCRIBE `{$table}`")->fetchAll(PDO::FETCH_ASSOC);

        return array_map(
            function ($field) {
                preg_match('#^(\w+)(\((.+)\))?( unsigned)?#', $field['Type'], $matches);

                $info = [
                    'name' => $field['Field'],
                    'type' => $matches[1],
                    'null' => ($field['Null'] === 'YES'),
                    'default' => $field['Default'],
                    'unsigned' => !empty($matches[4]),
                    'length' => null,
                    'values' => null,
                ];

                switch ($info['type']) {
                    case 'enum':
                    case 'set':
                        $info['values'] = explode(',', $matches[3]);
                        break;
                    default:
                        if (!isset($matches[3])) {
                            $info['length'] = null;
                        } elseif (strpos($matches[3], ',')) {
                            $info['length'] = floatval(str_replace(',', '.', $matches[3]));
                        } else {
                            $info['length'] = intval($matches[3]);
                        }
                }

                return $info;
            },
            $result
        );
    }
}
