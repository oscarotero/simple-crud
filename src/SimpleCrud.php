<?php
declare(strict_types = 1);

namespace SimpleCrud;

use Exception;
use Latitude\QueryBuilder\Query;
use Latitude\QueryBuilder\QueryFactory;
use PDO;
use PDOStatement;
use RuntimeException;

class SimpleCrud
{
    const ENGINE_MYSQL = 'mysql';
    const ENGINE_SQLITE = 'sqlite';
    const ATTR_LOCALE = 'simplecrud.language';

    protected $connection;
    protected $scheme;
    protected $tables = [];
    protected $inTransaction = false;
    protected $attributes = [];
    protected $onExecute;

    protected $tableFactory;
    protected $queryFactory;
    protected $fieldFactory;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Set the database scheme.
     */
    public function setScheme(array $scheme): self
    {
        $this->scheme = $scheme;

        return $this;
    }

    /**
     * Get the engine type
     */
    public function getEngineType(): string
    {
        $engine = $this->getAttribute(PDO::ATTR_DRIVER_NAME);

        switch ($engine) {
            case self::ENGINE_MYSQL:
            case self::ENGINE_SQLITE:
                return $engine;
            default:
                throw new RuntimeException("Invalid engine type {$engine}");
        }
    }

    /**
     * Get the namespace for the engine used
     */
    public function getEngineNamespace(): string
    {
        return 'SimpleCrud\\Engine\\'.ucfirst($this->getEngineType()).'\\';
    }

    /**
     * Returns the database scheme.
     */
    public function getScheme(): array
    {
        if ($this->scheme === null) {
            $builder = $this->getEngineNamespace().'SchemeBuilder';
            $this->setScheme($builder::buildScheme($this));
        }

        return $this->scheme;
    }

    /**
     * Define a callback executed for each query executed.
     */
    public function onExecute(callable $callback = null): self
    {
        $this->onExecute = $callback;

        return $this;
    }

    /**
     * Set the TableFactory instance used to create all tables.
     */
    public function setTableFactory(TableFactory $tableFactory): self
    {
        $this->tableFactory = $tableFactory;

        return $this;
    }

    /**
     * Returns the TableFactory instance used.
     */
    public function getTableFactory(): TableFactory
    {
        if ($this->tableFactory === null) {
            return $this->tableFactory = (new TableFactory())->setAutocreate();
        }

        return $this->tableFactory;
    }

    /**
     * Returns the QueryFactory instance used to create the queries.
     */
    public function query(): QueryFactory
    {
        if ($this->queryFactory === null) {
            $builder = $this->getEngineNamespace().'QueryFactoryBuilder';
            $this->queryFactory = $builder::buildQueryFactory($this);
        }

        return $this->queryFactory;
    }

    /**
     * Set the FieldFactory instance used by the tables.
     */
    public function setFieldFactory(FieldFactory $fieldFactory): self
    {
        $this->fieldFactory = $fieldFactory;

        return $this;
    }

    /**
     * Returns the FieldFactory instance used by the tables.
     */
    public function getFieldFactory(): FieldFactory
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
    public function __get($name): Table
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
     * @throws Exception
     */
    public function execute(string $query, array $marks = null): PDOStatement
    {
        $statement = $this->connection->prepare((string) $query);
        $statement->execute($marks);

        if ($this->onExecute !== null) {
            call_user_func($this->onExecute, $this->connection, $statement, $marks);
        }

        return $statement;
    }

    /**
     * Execute a callable inside a transaction.
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
     */
    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }

    /**
     * Starts a transaction if it's not started yet.
     */
    public function beginTransaction(): bool
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
     * @param mixed $value
     *
     * @return $this
     */
    public function setAttribute(string $name, $value): self
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
