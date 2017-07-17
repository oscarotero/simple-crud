<?php

namespace SimpleCrud;

use Exception;
use PDO;

class SimpleCrud
{
    const ATTR_LOCALE = 'simplecrud.language';
    const ATTR_UPLOADS = 'simplecrud.uploads';

    protected $connection;
    protected $scheme;
    protected $tables = [];
    protected $inTransaction = false;
    protected $attributes = [];
    protected $onExecute;

    protected $tableFactory;
    protected $queryFactory;
    protected $fieldFactory;

    /**
     * Set the connection.
     *
     * @param PDO $connection
     */
    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Set the database scheme.
     * 
     * @param array $scheme
     * 
     * @return self
     */
    public function setScheme(array $scheme)
    {
        $this->scheme = $scheme;

        return $this;
    }

    /**
     * Returns the database scheme.
     * 
     * @return array
     */
    public function getScheme()
    {
        if ($this->scheme === null) {
            $class = 'SimpleCrud\\Scheme\\'.ucfirst($this->getAttribute(PDO::ATTR_DRIVER_NAME));

            if (!class_exists($class)) {
                throw new SimpleCrudException(sprintf('Scheme class "%s" not found', $class));
            }

            $factory = new $class($this);

            $this->setScheme($factory());
        }

        return $this->scheme;
    }

    /**
     * Define a callback executed for each query executed.
     *
     * @param callable|null $callback
     */
    public function onExecute(callable $callback = null)
    {
        $this->onExecute = $callback;
    }

    /**
     * Set the TableFactory instance used to create all tables.
     *
     * @param TableFactory $tableFactory
     * 
     * @return self
     */
    public function setTableFactory(TableFactory $tableFactory)
    {
        $this->tableFactory = $tableFactory;

        return $this;
    }

    /**
     * Returns the TableFactory instance used.
     *
     * @return TableFactory
     */
    public function getTableFactory()
    {
        if ($this->tableFactory === null) {
            return $this->tableFactory = (new TableFactory())->setAutocreate();
        }

        return $this->tableFactory;
    }

    /**
     * Set the QueryFactory instance used by the tables.
     *
     * @param QueryFactory $queryFactory
     *
     * @return self
     */
    public function setQueryFactory(QueryFactory $queryFactory)
    {
        $this->queryFactory = $queryFactory;

        return $this;
    }

    /**
     * Returns the QueryFactory instance used by the tables.
     *
     * @return QueryFactory
     */
    public function getQueryFactory()
    {
        if ($this->queryFactory === null) {
            $queryFactory = new QueryFactory();
            $queryFactory->addNamespace('SimpleCrud\\Queries\\'.ucfirst($this->getAttribute(PDO::ATTR_DRIVER_NAME)).'\\');

            return $this->queryFactory = $queryFactory;
        }

        return $this->queryFactory;
    }

    /**
     * Set the FieldFactory instance used by the tables.
     *
     * @param FieldFactory $fieldFactory
     *
     * @return self
     */
    public function setFieldFactory(FieldFactory $fieldFactory)
    {
        $this->fieldFactory = $fieldFactory;

        return $this;
    }

    /**
     * Returns the FieldFactory instance used by the tables.
     *
     * @return FieldFactory
     */
    public function getFieldFactory()
    {
        if ($this->fieldFactory === null) {
            return $this->fieldFactory = new FieldFactory();
        }

        return $this->fieldFactory;
    }

    /**
     * Magic method to initialize the tables in lazy mode.
     *
     * @param string $name The table name
     *
     * @throws SimpleCrudException If the table cannot be instantiated
     *
     * @return Table
     */
    public function __get($name)
    {
        if (isset($this->tables[$name])) {
            return $this->tables[$name];
        }

        return $this->tables[$name] = $this->getTableFactory()->get($this, $name);
    }

    /**
     * Magic method to check if a table exists or not.
     *
     * @param string $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->getScheme()[$name]);
    }

    /**
     * Execute a query and returns the statement object with the result.
     *
     * @param string $query The Mysql query to execute
     * @param array  $marks The marks passed to the statement
     *
     * @throws Exception On error preparing or executing the statement
     *
     * @return PDOStatement The result
     */
    public function execute($query, array $marks = null)
    {
        $query = (string) $query;

        if (!empty($marks)) {
            foreach ($marks as $name => $mark) {
                if (is_array($mark)) {
                    foreach ($mark as &$val) {
                        $val = $this->connection->quote($val);
                    }

                    $query = str_replace($name, implode(', ', $mark), $query);
                    unset($marks[$name]);
                }
            }

            if (empty($marks)) {
                $marks = null;
            }
        }

        $statement = $this->connection->prepare($query);
        $statement->execute($marks);

        if ($this->onExecute !== null) {
            call_user_func($this->onExecute, $this->connection, $statement, $marks);
        }

        return $statement;
    }

    /**
     * Execute a callable inside a transaction.
     *
     * @param callable $callable The function with all operations
     *
     * @return mixed The callable returned value
     */
    public function executeTransaction(callable $callable)
    {
        try {
            $transaction = $this->beginTransaction();

            $return = $callable($this);

            if ($transaction) {
                $this->commit();
            }
        } catch (Exception $exception) {
            if ($transaction) {
                $this->rollBack();
            }

            throw $exception;
        }

        return $return;
    }

    /**
     * Returns the last insert id.
     *
     * @return string
     */
    public function lastInsertId()
    {
        return $this->connection->lastInsertId();
    }

    /**
     * Starts a transaction if it's not started yet.
     *
     * @return bool True if a the transaction is started or false if don't
     */
    public function beginTransaction()
    {
        if (!$this->inTransaction()) {
            $this->connection->beginTransaction();

            return $this->inTransaction = true;
        }

        return false;
    }

    /**
     * Commits the changes of the transaction to the database.
     */
    public function commit()
    {
        if ($this->inTransaction()) {
            $this->connection->commit();
            $this->inTransaction = false;
        }
    }

    /**
     * RollBack a transaction.
     */
    public function rollBack()
    {
        if ($this->inTransaction()) {
            $this->connection->rollBack();
            $this->inTransaction = false;
        }
    }

    /**
     * Check if there is a transaction opened currently in this adapter.
     */
    public function inTransaction()
    {
        return ($this->inTransaction === true) && ($this->connection->inTransaction() === true);
    }

    /**
     * Saves a new attribute.
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return $this
     */
    public function setAttribute($name, $value)
    {
        if (is_int($name)) {
            $this->connection->setAttribute($name, $value);
        } else {
            $this->attributes[$name] = $value;
        }

        return $this;
    }

    /**
     * Returns an attribute.
     *
     * @param string|int $name
     *
     * @return null|mixed
     */
    public function getAttribute($name)
    {
        if (is_int($name)) {
            return $this->connection->getAttribute($name);
        }

        return isset($this->attributes[$name]) ? $this->attributes[$name] : null;
    }
}
