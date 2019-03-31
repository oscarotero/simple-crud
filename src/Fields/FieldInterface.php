<?php
declare(strict_types = 1);

namespace SimpleCrud\Fields;

use Atlas\Query\Insert;
use Atlas\Query\Select;
use Atlas\Query\Update;

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
     * Returns the field full name (ex: `tableName`.`fieldName`)
     */
    public function __toString();

    /**
     * Add the field in an Select query
     */
    public function select(Select $query);

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
     * Format and returns the value ready to use
     * @param mixed $value
     */
    public function format($value);

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
