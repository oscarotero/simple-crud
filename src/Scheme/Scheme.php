<?php

namespace SimpleCrud\Scheme;

use SimpleCrud\SimpleCrud;

/**
 * Base class used by all queries.
 */
abstract class Scheme
{
    const HAS_ONE = 1;
    const HAS_MANY = 2;
    const HAS_MANY_TO_MANY = 4;

    protected $db;

    /**
     * Constructor.
     *
     * @param SimpleCrud $db
     */
    public function __construct(SimpleCrud $db)
    {
        $this->db = $db;
    }

    /**
     * Return the database scheme.
     * 
     * @return array
     */
    public function __invoke()
    {
        $scheme = [];

        foreach ($this->getTables() as $table) {
            $scheme[$table] = [
                'fields' => $this->getTableFields($table),
                'relations' => [],
            ];
        }

        foreach ($scheme as $table => &$info) {
            $foreingKey = "{$table}_id";

            foreach ($scheme as $relTable => &$relInfo) {
                if (isset($relInfo['fields'][$foreingKey])) {
                    $info['relations'][$relTable] = [self::HAS_MANY, $foreingKey];

                    if ($table === $relTable) {
                        $relInfo['relations'][$table] = [self::HAS_MANY, $foreingKey];
                    } else {
                        $relInfo['relations'][$table] = [self::HAS_ONE, $foreingKey];
                    }
                    continue;
                }

                if ($table < $relTable) {
                    $bridge = "{$table}_{$relTable}";
                } else {
                    $bridge = "{$relTable}_{$table}";
                }

                if (isset($scheme[$bridge])) {
                    $relForeingKey = "{$relTable}_id";

                    if (isset($scheme[$bridge]['fields'][$foreingKey]) && isset($scheme[$bridge]['fields'][$relForeingKey])) {
                        $info['relations'][$relTable] = [self::HAS_MANY_TO_MANY, $bridge, $foreingKey, $relForeingKey];
                        $relInfo['relations'][$table] = [self::HAS_MANY_TO_MANY, $bridge, $relForeingKey, $foreingKey];
                    }
                }
            }
        }

        return $scheme;
    }

    /**
     * Return all tables.
     *
     * @return array
     */
    abstract protected function getTables();

    /**
     * Return the scheme of a table.
     * 
     * @param string $table
     *
     * @return array
     */
    abstract protected function getTableFields($table);
}
