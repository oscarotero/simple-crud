<?php
declare(strict_types = 1);

namespace SimpleCrud\Queries;

use Closure;
use PDO;
use SimpleCrud\Row;
use SimpleCrud\Table;

class Select extends Query
{
    use Traits\HasRelatedWith;
    use Traits\HasPagination;
    use Traits\HasJoinRelation;

    private $one;
    protected const ALLOWED_METHODS = [
        'from',
        'columns',
        'join',
        'catJoin',
        'groupBy',
        'having',
        'orHaving',
        'orderBy',
        'catHaving',
        'where',
        'whereSprintf',
        'whereEquals',
        'orWhere',
        'orWhereSprinf',
        'catWhere',
        'limit',
        'offset',
        'distinct',
        'forUpdate',
        'setFlag',
        'bindValue',
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
    }

    public function one(): self
    {
        $this->one = true;
        $this->query->limit(1);

        return $this;
    }

    public function run()
    {
        $data = $this->getArray();

        if ($this->one) {
            return $data ? $this->createRow($data) : null;
        }

        $rows = array_map(Closure::fromCallable([$this, 'createRow']), $data);

        return $this->table->createCollection($rows);
    }

    public function getArray(): ?array
    {
        $statement = $this->__invoke();
        $statement->setFetchMode(PDO::FETCH_ASSOC);

        if ($this->one) {
            return $statement->fetch() ?: null;
        }

        return $statement->fetchAll();
    }

    private function createRow(array $data): Row
    {
        $data = $this->table->format($data);
        $fields = $this->table->getFields();
        $extraData = array_diff_key($data, $fields);
        $values = array_intersect_key($data, $fields);

        return $this->table->create($values)->setData($extraData);
    }
}
