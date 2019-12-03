<?php
declare(strict_types = 1);

namespace SimpleCrud\Fields;

use Atlas\Query\Insert;
use Atlas\Query\Select;
use Atlas\Query\Update;

final class Point extends Field
{
    public static function getFactory(): FieldFactory
    {
        return new FieldFactory(
            self::class,
            ['point']
        );
    }

    public function select(Select $query): void
    {
        $name = $this->getName();
        $query->columns("ST_AsText({$this}) as `{$name}`");
    }

    public function format($value): ?array
    {
        if ($value === null) {
            return null;
        }

        //POINT(X Y)
        $points = explode(' ', substr((string) $value, 6, -1), 2);

        return [
            floatval($points[0]),
            floatval($points[1]),
        ];
    }

    public function insert(Insert $query, $value): void
    {
        if (self::isValid($value)) {
            $value = sprintf('POINT(%s, %s)', $value[0], $value[1]);
        } else {
            $value = null;
        }

        $query->set($this->info['name'], $value);
    }

    public function update(Update $query, $value): void
    {
        if (self::isValid($value)) {
            $value = sprintf('POINT(%s, %s)', $value[0], $value[1]);
        } else {
            $value = null;
        }

        $query->set($this->info['name'], $value);
    }

    /**
     * Check whether the value is valid before save in the database
     * @param mixed $data
     */
    private static function isValid($data): bool
    {
        if (!is_array($data)) {
            return false;
        }

        if (!isset($data[0]) || !isset($data[1]) || count($data) > 2) {
            return false;
        }

        return is_numeric($data[0]) && is_numeric($data[1]);
    }
}
