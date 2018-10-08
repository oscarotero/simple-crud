<?php
declare(strict_types = 1);

namespace SimpleCrud\Query\Traits;

use PDO;
use SimpleCrud\Table;

trait Aggregation
{
    use Common;

    private $field;

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

        $result = $statement->fetch();
        $field = $this->table->{$this->field};

        return $field->format($result[0]);
    }
}
