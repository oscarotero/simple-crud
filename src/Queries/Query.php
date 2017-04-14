<?php

namespace SimpleCrud\Queries;

use SimpleCrud\Table;

/**
 * Base class used by all queries.
 */
abstract class Query
{
    protected $table;

    /**
     * Constructor.
     *
     * @param Table $table
     */
    public function __construct(Table $table)
    {
        $this->table = $table;
    }

    /**
     * Run the query and return the statement object.
     *
     * @return \PDOStatement
     */
    abstract public function __invoke();

    /**
     * Build and return the query.
     *
     * @return string
     */
    abstract public function __toString();

    /**
     * Executes the query and returns true if it's ok.
     * 
     * @return mixed
     */
    public function run()
    {
        return $this->__invoke();
    }
}
