<?php
declare(strict_types = 1);

namespace SimpleCrud\Query;

use PDO;
use SimpleCrud\Table;

final class Select implements QueryInterface
{
    use Traits\CommonsTrait;
    use Traits\WhereTrait;

    private $one;
    private $cache;

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

    public function page(int $page, int $length): self
    {
        if ($page < 1) {
            $page = 1;
        }

        $this->query->limit($length);
        $this->query->offset(($page * $length) - $length);

        return $this;
    }

    public function run()
    {
        $statement = $this->__invoke();
        $statement->setFetchMode(PDO::FETCH_ASSOC);

        if ($this->cache) {
            if ($this->one) {
                $data = $statement->fetch();

                return $data ? $this->combine($data, $this->cache) : null;
            }

            return $this->combine($statement->fetchAll(), $this->cache);
        }

        if ($this->one) {
            $data = $statement->fetch();

            return $data ? $this->table->create($data, true) : null;
        }

        return $this->table->createCollection($statement->fetchAll(), true);
    }
}
