<?php

namespace SimpleCrud\Queries\Mysql;

use SimpleCrud\Queries\BaseQuery;
use SimpleCrud\Queries\SelectTrait;
use SimpleCrud\RowCollection;
use SimpleCrud\Entity;
use PDOStatement;
use PDO;

/**
 * Manages a database select query.
 */
class SelectAll extends BaseQuery
{
    use SelectTrait;

    /**
     * Run the query and return all values.
     *
     * @param bool $idAsKey
     *
     * @return RowCollection
     */
    public function get($idAsKey = true)
    {
        $statement = $this->run();
        $result = $this->entity->createCollection();

        $result->idAsKey($idAsKey);

        while (($row = $statement->fetch())) {
            $result[] = $this->entity->create($this->entity->prepareDataFromDatabase($row));
        }

        return $result;
    }
}
