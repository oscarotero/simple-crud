<?php
declare(strict_types = 1);

namespace SimpleCrud\Queries;

use InvalidArgumentException;
use PDO;
use SimpleCrud\Table;

final class SelectAggregate extends Select
{
    private $field;
    private const AGGREGATION_FUNCTIONS = [
        'AVG',
        'COUNT',
        'MAX',
        'MIN',
        'SUM',
    ];

    public function __construct(Table $table, string $function, string $field = 'id', string $as = null)
    {
        $this->table = $table;
        $this->field = $field;

        $function = strtoupper($function);

        if (!in_array($function, self::AGGREGATION_FUNCTIONS)) {
            throw new InvalidArgumentException(
                sprintf('Invalid aggregation function. Must be one of the followings: %s', implode(', ', self::AGGREGATION_FUNCTIONS))
            );
        }

        if ($as) {
            $columns = sprintf('%s(%s) AS `%s`', $function, $field, $as);
        } else {
            $columns = sprintf('%s(%s)', $function, $field);
        }

        $this->query = $table->getDatabase()
            ->select()
            ->from((string) $table)
            ->columns($columns);
    }

    public function run()
    {
        $statement = $this->__invoke();
        $statement->setFetchMode(PDO::FETCH_NUM);
        $result = $statement->fetchColumn();

        $field = $this->table->{$this->field} ?? null;

        return $field ? $field->format($result) : $result;
    }
}
