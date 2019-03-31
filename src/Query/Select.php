<?php
declare(strict_types = 1);

namespace SimpleCrud\Query;

use Closure;
use PDO;
use SimpleCrud\Events\CreateSelectQuery;
use SimpleCrud\Table;

final class Select implements QueryInterface
{
    use Traits\Common;
    use Traits\HasRelatedWith;
    use Traits\HasPagination;
    use Traits\HasJoinRelation;

    private $one;
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

    public function __construct(Table $table)
    {
        $this->table = $table;

        $this->query = $table->getDatabase()
            ->select()
            ->from((string) $table);

        foreach ($table->getFields() as $field) {
            $field->select($this->query);
        }

        $eventDispatcher = $table->getEventDispatcher();

        if ($eventDispatcher) {
            $eventDispatcher->dispatch(new CreateSelectQuery($this));
        }
    }

    public function one(): self
    {
        $this->one = true;
        $this->query->limit(1);

        return $this;
    }

    public function run()
    {
        $statement = $this->__invoke();
        $statement->setFetchMode(PDO::FETCH_ASSOC);

        if ($this->one) {
            $data = $statement->fetch();

            return $data ? $this->table->create($this->formatRow($data)) : null;
        }

        $data = array_map(Closure::fromCallable([$this, 'formatRow']), $statement->fetchAll());

        return $this->table->createCollection($data);
    }

    private function formatRow(array $data): array
    {
        foreach ($data as $fieldName => &$value) {
            $value = $this->table->{$fieldName}->format($value);
        }

        return $data;
    }
}
