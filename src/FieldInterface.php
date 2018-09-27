<?php
declare(strict_types = 1);

namespace SimpleCrud;

use Atlas\Query\Insert;
use Atlas\Query\Update;
use Latitude\QueryBuilder\Builder\CriteriaBuilder;
use Latitude\QueryBuilder\StatementInterface;

/**
 * Interface used by Field classes.
 */
interface FieldInterface
{
    /**
     * Returns the field name
     */
    public function getName(): string;

    /**
     * Returns the identity statement used in database queries
     */
    public function getFullname(): string;

    /**
     * Returns the criteria used in the database queries
     */
    public function criteria(): CriteriaBuilder;

    /**
     * Add the field in an Insert query
     * @param mixed $value
     */
    public function insert(Insert $query, $value);

    /**
     * Add the field in an Update query
     * @param mixed $value
     */
    public function update(Update $query, $value);

    /**
     * Format and returns the value ready to be saved in the database
     * @param mixed $value
     */
    public function databaseValue($value);

    /**
     * Format and returns the value ready to the Row instance
     * @param mixed $value
     */
    public function rowValue($value);

    /**
     * Return a configuration value
     */
    public function getConfig(string $name);

    /**
     * Set a configuration value
     * @param mixed $value
     */
    public function setConfig(string $name, $value);
}
