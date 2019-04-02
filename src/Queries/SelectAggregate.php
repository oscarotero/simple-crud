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

    public function __construct(Table $table, string $function, string $field = 'id')
    {
        $this->table = $table;
        $this->field = $field;

        $function = strtoupper($function);

        if (!in_array($function, self::AGGREGATION_FUNCTIONS)) {
            throw InvalidArgumentException(
                sprintf('Invalid aggregation function. Must be one of the followings: %s', implode(', ', self::AGGREGATION_FUNCTIONS))
            );
        }

        $this->query = $table->getDatabase()
            ->select()
            ->from((string) $table)
            ->columns(sprintf('%s(%s)', $function, $field));
    }

    public function run()
    {
        $statement = $this->__invoke();
        $statement->setFetchMode(PDO::FETCH_NUM);

        $field = $this->table->{$this->field};

        return $field->format($statement->fetchColumn());
    }
}
