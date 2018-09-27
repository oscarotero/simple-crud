<?php

namespace SimpleCrud\Fields;

use Atlas\Query\Insert;
use Atlas\Query\Update;

final class Point extends Field
{
    /**
     * {@inheritdoc}
     */
    public function getSelectExpression($as = null)
    {
        $fieldName = $this->info['name'];

        if ($as) {
            return "asText({$this}) as `{$as}`";
        }

        return "asText({$this}) as `{$fieldName}`";
    }

    /**
     * {@inheritdoc}
     */
    public function getValueExpression($mark)
    {
        return "PointFromText($mark)";
    }

    /**
     * {@inheritdoc}
     */
    public function rowValue($value, array $data = [])
    {
        //POINT(X Y)
        if ($value !== null) {
            $points = explode(' ', substr($value, 6, -1), 2);

            return [
                floatval($points[0]),
                floatval($points[1]),
            ];
        }
    }

    public function insert(Insert $query, $value)
    {
        if (self::isValid($value)) {
            $value = sprintf('POINT(%s, %s)', $value[0], $value[1]);
        } else {
            $value = null;
        }

        $query->set($this->info['name'], $value);
    }

    public function update(Update $query, $value)
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
