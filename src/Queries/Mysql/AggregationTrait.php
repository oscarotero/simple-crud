<?php

namespace SimpleCrud\Queries\Mysql;

use PDO;

/**
 * Trait with common funcions used in aggregation queries (min,max,avg,count,sum).
 *
 * @property \SimpleCrud\Table $table
 */
trait AggregationTrait
{
    use ExtendedSelectionTrait;
    protected $field;

    /**
     * Set the field name to sum over.
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
     * Run the query and return the value.
     * 
     * @return mixed
     */
    public function run()
    {
        $result = $this->__invoke()->fetch();
        $field = $this->table->{$this->field};

        return $field->dataFromDatabase($result[0]);
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
        $statement = $this->table->getDatabase()->execute((string) $this, $this->marks);
        $statement->setFetchMode(PDO::FETCH_NUM);

        return $statement;
    }

    /**
     * Build and return the query.
     *
     * @return string
     */
    public function __toString()
    {
        $query = "SELECT ".self::AGGREGATION_FUNCTION."(`{$this->field}`) FROM `{$this->table->getName()}`";

        $query .= $this->fromToString();
        $query .= $this->whereToString();
        $query .= $this->limitToString();

        return $query;
    }
}
