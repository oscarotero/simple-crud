<?php
namespace SimpleCrud;

use SimpleCrud\Factory;
use SimpleCrud\SimpleCrudException;
use Exception;
use PDO;


class SimpleCrud
{
    protected $connection;
    protected $inTransaction = false;
    protected $factory;
    protected $entities = [];
    protected $attributes = [];

    /**
     * Set the connection and the entityFactory.
     *
     * @param PDO           $connection
     * @param EntityFactory $entityFactory
     */
    public function __construct(PDO $connection, Factory $factory = null)
    {
        if ($factory === null) {
            $factory = (new Factory())->autocreate();
        }

        $this->connection = $connection;
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->factory = $factory;
        $this->factory->init($this);
    }

    /**
     * Magic method to initialize the entities in lazy mode.
     *
     * @param string $name The entity name
     *
     * @throws SimpleCrudException If the entity cannot be instantiated
     *
     * @return null|Entity
     */
    public function __get($name)
    {
        if (isset($this->entities[$name])) {
            return $this->entities[$name];
        }

        $entity = $this->factory->getEntity($name);

        if (empty($entity)) {
            throw new SimpleCrudException("The entity '{$name}' is not valid");
        }

        return $this->entities[$name] = $entity;
    }

    /**
     * Magic method to check if a entity exists or not.
     *
     * @param string $name
     *
     * @return boolean
     */
    public function __isset($name)
    {
        return isset($this->entities[$name]) || $this->factory->hasEntity($name);
    }

    /**
     * Magic method to create queries related with entities
     * 
     * @param string $name
     * @param array  $arguments
     * 
     * @throws SimpleCrudException
     * 
     * @return QueryInterface|null
     */
    public function __call($name, $arguments)
    {
        $entity = array_shift($arguments);

        if ($entity) {
            return $this->factory->getQuery($this->$entity, $name);
        }
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

            $return = $callable();

            if ($transaction) {
                $this->commit();
            }
        } catch (\Exception $exception) {
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
     * @return boolean True if a the transaction is started or false if don't
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
     * Check if there is a transaction opened currently in this adapter
     */
    public function inTransaction()
    {
        return ($this->inTransaction === true) && ($this->connection->inTransaction() === true);
    }

    /**
     * Saves a new attribute
     * 
     * @param string $name
     * @param mixed  $value
     * 
     * @return $this
     */
    public function setAttribute($name, $value)
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    /**
     * Returns an attribute
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

    /**
     * Returns the factory instance
     * 
     * @return Factory
     */
    public function getFactory()
    {
        return $this->factory;
    }
}
