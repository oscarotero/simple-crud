<?php
declare(strict_types = 1);

namespace SimpleCrud\Engine\Common;

use SimpleCrud\SimpleCrud;
use SimpleCrud\Table;
use SimpleCrud\Engine\SchemeInterface;
use Latitude\QueryBuilder\Query\SelectQuery;
use function Latitude\QueryBuilder\criteria;

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

    public function applyRelationCriteria(SelectQuery $query, Table $table1, Table $table2, $values)
    {
        switch ($this->getRelation($table1, $table2)) {
            case SchemeInterface::HAS_ONE:
                $criteria = $table1->{$table2->getForeignKey()}->criteria();
                break;

            case SchemeInterface::HAS_MANY:
                $field = $table1 === $table2 ? $table2->getForeignKey() : 'id';
                $criteria = $table1->{$field}->criteria();
                break;

            case SchemeInterface::HAS_MANY_TO_MANY:
                $bridge = $this->getManyToManyTableName($table1, $table2);
                $bridge = $this->db->{$bridge};
                $criteria = $bridge->{$table2->getForeignKey()}->criteria();

                $query
                    ->addFrom($bridge->getName())
                    ->addColumns($bridge->{$table2->getForeignKey()}->identify())
                    ->andWhere(criteria(
                        '%s = %s',
                        $bridge->{$table1->getForeignKey()}->identify(),
                        $table1->id->identify()
                    ));
                break;

            default:
                throw new \Exception("Error Processing Request", 1);
        }

        if (is_array($value)) {
            $query->andWhere($criteria->in(...$values));
        } else {
            $query->andWhere($criteria->eq($values));
        }
    }

    public function getManyToManyTableName(Table $table1, Table $table2): string
    {
        $name1 = $table1->getName();
        $name2 = $table2->getName();

        return $name1 < $name2 ? "{$name1}_{$name2}" : "{$name2}_{$name1}";
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
