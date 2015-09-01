<?php
namespace SimpleCrud\Queries;

use SimpleCrud\Entity;
use PDOStatement;

/**
 * Manages a database delete query in Mysql databases
 */
class Delete extends BaseQuery
{
    use WhereTrait;
    use LimitTrait;

    /**
     * Adds new marks to the query
     *
     * @param array $marks
     *
     * @return self
     */
    public function marks(array $marks)
    {
        $this->marks += $marks;

        return $this;
    }

    /**
     * Run the query and return all values
     *
     * @return PDOStatement
     */
    public function run()
    {
        return $this->entity->getDb()->execute((string) $this, $this->marks);
    }

    /**
     * Build and return the query
     *
     * @return string
     */
    public function __toString()
    {
        $query = "DELETE FROM `{$this->entity->name}`";

        $query .= $this->whereToString();
        $query .= $this->limitToString();

        return $query;
    }
}
