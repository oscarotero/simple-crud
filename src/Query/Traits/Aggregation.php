<?php
declare(strict_types = 1);

namespace SimpleCrud\Query\Traits;

use PDO;
use SimpleCrud\Table;

trait Aggregation
{
    use Common;
    use HasRelatedWith;
    use HasPagination;
    use HasJoinRelation;

    private $field;
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

    public function __construct(Table $table, string $field = 'id')
    {
        $this->table = $table;
        $this->field = $field;

        $this->query = $table->getDatabase()
            ->select()
            ->from((string) $table)
            ->columns(sprintf('%s(%s)', self::AGGREGATION_FUNCTION, $field));
    }

    public function run()
    {
        $statement = $this->__invoke();
        $statement->setFetchMode(PDO::FETCH_NUM);

        $field = $this->table->{$this->field};

        return $field->format($statement->fetchColumn());
    }
}
