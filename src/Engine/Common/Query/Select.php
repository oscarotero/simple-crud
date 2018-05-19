<?php
declare(strict_types = 1);

namespace SimpleCrud\Engine\Common\Query;

use PDO;
use SimpleCrud\Engine\QueryInterface;
use SimpleCrud\Row;
use SimpleCrud\RowCollection;
use SimpleCrud\Table;
use InvalidArgumentException;
use RuntimeException;

abstract class Select implements QueryInterface
{
    use QueryTrait;
    use WhereTrait;
    use CombinatorTrait;

    private $one;
    private $cache;

    public function __construct(Table $table)
    {
        $this->init($table);

        $fields = array_map(
            function ($field) {
                return $field->identify();
            },
            array_values($table->getFields())
        );

        $this->query = $this->builder
            ->select(...$fields)
            ->from($table->getName());
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

    /**
     * @param Row|RowCollection
     * @param mixed $related
     */
    public function cacheWith($related): self
    {
        if ($related instanceof Row || $related instanceof RowCollection) {
            if ($this->cache) {
                throw new RuntimeException('Only one cache is allowed');
            }

            $this->cache = $related;

            if ($joinTable = $this->table->getJoinTable($related->getTable())) {
                $field = $joinTable->getJoinField($related->getTable());

                $this->addColumns($field->identify());
            }

            return $this;
        }

        throw new InvalidArgumentException(
            'Invalid argument type. Only instances of Row and RowCollection are allowed'
        );
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
