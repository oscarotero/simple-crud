<?php
declare(strict_types = 1);

namespace SimpleCrud\Query;

use InvalidArgumentException;
use PDO;
use SimpleCrud\Table;

final class SelectAggregate implements QueryInterface
{
    use Traits\Common;
    use Traits\HasRelatedWith;
    use Traits\HasPagination;
    use Traits\HasJoinRelation;

    private $field;
    private const AGGREGATION_FUNCTIONS = [
        'AVG',
        'COUNT',
        'MAX',
        'MIN',
        'SUM',
    ];
    private $allowedMethods = [
        'from',
        'join',
        'catJoin',
        'groupBy',
        'having',
        'orHaving',
        'orderBy',
        'catHaving',
        'where',
        'orWhere',
        'catWhere',
        'limit',
        'offset',
        'distinct',
        'forUpdate',
        'setFlag',
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
