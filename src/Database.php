<?php
declare(strict_types = 1);

namespace SimpleCrud;

use Atlas\Pdo\Connection;
use Atlas\Query\Insert;
use Atlas\Query\Update;
use Atlas\Query\Delete;
use Atlas\Query\Select;
use Atlas\Query\Bind;

use Exception;
use InvalidArgumentException;
use PDO;
use PDOStatement;
use RuntimeException;

class Database
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

    protected $queryFactory;
    protected $fieldFactory;

    public function __construct(PDO $pdo, SchemeInterface $scheme = null)
    {
        $this->connection = new Connection($pdo);
        $this->scheme = $scheme;
    }

    /**
     * Get the engine type
     */
    public function getEngineType(): string
    {
        $engine = $this->connection->getDriverName();

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
     * Return the scheme class
     */
    public function getScheme(): SchemeInterface
    {
        if ($this->scheme === null) {
            $scheme = $this->getEngineNamespace().'Scheme';
            $this->scheme = new $scheme($this);
        }

        return $this->scheme;
    }

    /**
     * Returns the connection instance.
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    public function select(): Select
    {
        return new Select($this->connection, new Bind());
    }

    public function update(): Update
    {
        return new Update($this->connection, new Bind());
    }

    public function delete(): Delete
    {
        return new Delete($this->connection, new Bind());
    }

    public function insert(): Insert
    {
        return new Insert($this->connection, new Bind());
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
     * @throws SimpleCrudException If the table cannot be instantiated
     */
    public function __get(string $name): Table
    {
        if (isset($this->tables[$name])) {
            return $this->tables[$name];
        }

        if (!$this->__isset($name)) {
            throw new InvalidArgumentException(
                sprintf('The table "%s" does not exist', $name)
            );
        }

        return $this->tables[$name] = new Table($this, $name);
    }

    /**
     * Magic method to check if a table exists or not.
     */
    public function __isset(string $name): bool
    {
        return in_array($name, $this->getScheme()->getTables());
    }

    /**
     * Execute a query and returns the statement object with the result.
     *
     * @throws Exception
     */
    public function execute(string $query, array $marks = null): PDOStatement
    {
        $statement = $this->connection->prepare($query);
        $statement->execute($marks);

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
     */
    public function getAttribute($name)
    {
        if (is_int($name)) {
            return $this->connection->getAttribute($name);
        }

        return isset($this->attributes[$name]) ? $this->attributes[$name] : null;
    }
}
