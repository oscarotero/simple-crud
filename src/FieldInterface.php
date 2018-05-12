<?php
declare(strict_types = 1);

namespace SimpleCrud;

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
    public function identify(): StatementInterface;

    /**
     * Returns the criteria used in the database queries
     */
    public function criteria(): CriteriaBuilder;

    /**
     * Returns the statement used to save the value in database
     * @param mixed $value
     */
    public function param($value): StatementInterface;

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
