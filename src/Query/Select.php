<?php
declare(strict_types = 1);

namespace SimpleCrud\Query;

use Closure;
use PDO;
use SimpleCrud\Table;
use SimpleCrud\Row;

final class Select implements QueryInterface
{
    use Traits\Common;
    use Traits\HasRelatedWith;
    use Traits\HasPagination;
    use Traits\HasJoinRelation;

    private $one;
    private $allowedMethods = [
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

        $dataFields = [];

        if ($this->one) {
            $data = $statement->fetch();

            return $data ? $this->createRow($data) : null;
        }

        $rows = array_map(Closure::fromCallable([$this, 'createRow']), $statement->fetchAll());

        return $this->table->createCollection($rows);
    }

    private function createRow(array $data): Row
    {
        $values = [];
        $extraData = [];
        $fields = $this->table->getFields();

        foreach ($data as $name => $value) {
            if (isset($fields[$name])) {
                $values[$name] = $fields[$name]->format($value);
            } else {
                $extraData[$name] = $value;
            }
        }

        return $this->table->create($values)->setData($extraData);
    }
}
