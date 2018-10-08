<?php
declare(strict_types = 1);

namespace SimpleCrud\Query;

use PDO;
use SimpleCrud\Table;

final class Select implements QueryInterface
{
    use Traits\Common;
    use Traits\HasWhere;
    use Traits\HasOrderBy;
    use Traits\HasGroupBy;
    use Traits\HasLimit;

    private $one;

    public function __construct(Table $table)
    {
        $this->table = $table;

        $fields = array_map(
            function ($field) {
                return (string) $field;
            },
            array_values($table->getFields())
        );

        $this->query = $table->getDatabase()
            ->select()
            ->from((string) $table)
            ->columns(...$fields);
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

            return $data ? $this->table->create($data, true) : null;
        }

        return $this->table->createCollection($statement->fetchAll(), true);
    }
}
