<?php
namespace SimpleCrud\Queries;

use SimpleCrud\Entity;
use PDOStatement;
use PDO;

/**
 * Manages a database select count query in Mysql databases
 */
class Count extends BaseQuery
{
    use WhereTrait;
    use LimitTrait;

    protected $field;

    /**
     * Set the field name to count
     *
     * @param string $field
     *
     * @return self
     */
    public function field($field)
    {
        $this->field = $field;

        return $this;
    }

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
     * Run the query and return a statement with the result
     *
     * @return PDOStatement
     */
    public function run()
    {
        $statement = $this->entity->getDb()->execute((string) $this, $this->marks);
        $statement->setFetchMode(PDO::FETCH_NUM);

        return $statement;
    }

    /**
     * Run the query and return the value
     *
     * @return integer
     */
    public function get()
    {
        $result = $this->run()->fetch();

        return (int) $result[0];
    }

    /**
     * Build and return the query
     *
     * @return string
     */
    public function __toString()
    {
        $query = "SELECT SUM(`{$this->field}`) FROM `{$this->entity->name}`";

        $query .= $this->whereToString();
        $query .= $this->limitToString();

        return $query;
    }
}
